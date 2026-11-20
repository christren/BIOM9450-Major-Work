<?php
// Mutation upload
// The HTML form sends a file using POST and enctype="multipart/form-data".
// PHP checks the file, reads one tab-delimited row at a time, then inserts
// the corresponding Gene, Variant and SampleMutation records into MySQL.
// The prediction/model integration is not involved in uploading mutations.
require_once __DIR__ . '/app/layout.php';
require_professional();
$err = '';
$selectedPatient = int_param(
    $_GET['patient_id'] ?? ($_POST['patient_id'] ?? null)
) ?? 0;

// STEP 1: Parse and validate the uploaded tab-separated file (TSV).
// The original sample_data.sql file is never modified.
function parse_mutation_upload(array $file,string $barcode): array {
    if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new RuntimeException('Select a valid TSV or TXT file.');
    if (($file['size']??0)<=0 || $file['size']>5*1024*1024)throw new RuntimeException('Upload must be nonempty and no larger than 5 MB.');
    if (!preg_match('/\.(txt|tsv)$/i',(string)($file['name']??'')))throw new RuntimeException('Choose a .txt or .tsv file.');
    if (!is_uploaded_file((string)$file['tmp_name']))throw new RuntimeException('Invalid upload.');
    $f=fopen($file['tmp_name'],'rb');if(!$f)throw new RuntimeException('Could not open upload.');
    // These headers are required because each mutation needs a gene,
    // reference genome, chromosome, allele change and sample barcode.
    $required=['Hugo_Symbol','NCBI_Build','Chromosome','Start_Position','End_Position','Reference_Allele','Tumor_Seq_Allele2','Variant_Classification','Variant_Type','Tumor_Sample_Barcode'];
    try {
        // fgetcsv() also works for TSV files when the delimiter is "\\t".
        $header=fgetcsv($f,0,"\t",'"','');
        if(!$header)throw new RuntimeException('The file is empty.');
        $header[0]=preg_replace('/^\xEF\xBB\xBF/','',(string)$header[0]);
        if(count($header)!==count(array_unique($header)))throw new RuntimeException('Duplicate column headers found.');
        $missing=array_diff($required,$header);
        if($missing)throw new RuntimeException('Required TSV columns missing: '.implode(', ',$missing));
        // array_flip() lets us look up a column by its header name.
        // This means the file columns do not have to be in one exact order.
        $positions = array_flip($header);
        $rows = [];
        $line = 1;
        while(($values=fgetcsv($f,0,"\t",'"',''))!==false){
            $line++;
            if(count($values)===1 && trim((string)$values[0])==='')continue;
            if(count($values)!==count($header))throw new RuntimeException("Line $line has a different number of columns from the header.");
            // Read one column by its name instead of remembering index numbers.
            $field = function ($name) use ($values, $positions) {
                return trim((string)$values[$positions[$name]]);
            };
            $sample=$field('Tumor_Sample_Barcode');
            if($sample!==$barcode)throw new RuntimeException("Line $line belongs to sample '$sample', not '$barcode'. Upload one sample at a time.");
            $gene=$field('Hugo_Symbol');$build=$field('NCBI_Build');$chr=$field('Chromosome');
            $start=$field('Start_Position');$end=$field('End_Position');
            $ref=$field('Reference_Allele');$alt=$field('Tumor_Seq_Allele2');
            $classification=$field('Variant_Classification');$type=$field('Variant_Type');
            if($gene===''||$build===''||$chr===''||$ref===''||$alt===''||$classification===''||$type==='')throw new RuntimeException("Line $line is missing a required mutation value.");
            if(!preg_match('/^[1-9][0-9]{0,17}$/',$start)||!preg_match('/^[1-9][0-9]{0,17}$/',$end)|| (float)$end<(float)$start)throw new RuntimeException("Line $line has invalid genomic coordinates.");
            foreach(['gene'=>$gene,'build'=>$build,'chr'=>$chr,'ref'=>$ref,'alt'=>$alt,'classification'=>$classification,'type'=>$type] as $key=>$val){
                $limits=['gene'=>100,'build'=>30,'chr'=>20,'ref'=>10000,'alt'=>10000,'classification'=>100,'type'=>20];
                if(strlen($val)>$limits[$key])throw new RuntimeException("Line $line has an overlong $key value.");
            }
            $impact=isset($positions['IMPACT'])?clean_nullable($field('IMPACT')):null;
            $protein=isset($positions['HGVSp_Short'])?clean_nullable($field('HGVSp_Short')):null;
            if($impact!==null&&strlen($impact)>100)throw new RuntimeException("Line $line has an overlong IMPACT value.");
            if($protein!==null&&strlen($protein)>255)throw new RuntimeException("Line $line has an overlong protein change.");
            // Save the checked values in memory; INSERT happens only after
            // every row has passed validation.
            $rows[] = [$gene,$build,$chr,$start,$end,$ref,$alt,$classification,$type,$impact,$protein];
            if(count($rows)>1000)throw new RuntimeException('This importer accepts up to 1,000 mutation rows per file. Split larger files.');
        }
        if(!$rows)throw new RuntimeException('The file contains no mutation rows.');
        return $rows;
    } finally {fclose($f);}
}

// STEP 2: Insert the validated rows into the normalized MySQL tables.
// A transaction ensures all rows succeed together, or none are saved.
function import_mutations(int $patientId, string $barcode, string $sampleType, array $rows): array {
    $conn = mysqli_db();

    // SourceRow must be unique, so only one import is permitted at a time.
    // GET_LOCK belongs to the MySQL connection (not the PHP session).
    $lock = (int)query_value("SELECT GET_LOCK('oncoatlas_mutation_import', 10)");
    if ($lock !== 1) {
        throw new RuntimeException('Another mutation import is running. Try again.');
    }

    $added = 0;
    $skipped = 0;
    $newSample = false;

    try {
        // Changes are only saved if EVERY query succeeds.
        $conn->begin_transaction();

        // FOR UPDATE protects this existing sample while we import.
        $sample = query_one(
            'SELECT SampleID, PatientID FROM TumorSample
             WHERE TCGASampleBarcode = ? FOR UPDATE',
            [$barcode]
        );

        if ($sample && (int)$sample['PatientID'] !== $patientId) {
            throw new RuntimeException('This sample barcode belongs to a different patient.');
        }

        if (!$sample) {
            // New sample: link it to the selected patient.
            $stmt = sql_run(
                'INSERT INTO TumorSample (PatientID, TCGASampleBarcode, SampleType)
                 VALUES (?, ?, ?)',
                [$patientId, $barcode, $sampleType]
            );
            $stmt->close();
            $sampleId = (int)$conn->insert_id;
            $newSample = true;
        } else {
            $sampleId = (int)$sample['SampleID'];
        }

        $nextRow = (int)query_value(
            'SELECT COALESCE(MAX(SourceRow), 0) FROM SampleMutation'
        ) + 1;

        // For each validated file row, find/create the gene and variant,
        // then link them to this tumour sample through SampleMutation.
        foreach ($rows as $record) {
            [
                $gene, $build, $chr, $start, $end, $ref, $alt,
                $classification, $type, $impact, $protein
            ] = $record;

            // Gene.GeneSymbol is unique. Reuse its ID if it already exists.
            $stmt = sql_run(
                'INSERT INTO Gene (GeneSymbol) VALUES (?)
                 ON DUPLICATE KEY UPDATE GeneID = LAST_INSERT_ID(GeneID)',
                [$gene]
            );
            $stmt->close();
            $geneId = (int)$conn->insert_id;

            // Compare all six location/allele fields to find the variant.
            $variantId = query_value(
                'SELECT VariantID FROM Variant
                 WHERE GenomeBuild = ? AND Chromosome = ?
                   AND MutationPosition = ? AND EndPosition = ?
                   AND ReferenceAllele = ? AND AlternateAllele = ?
                 LIMIT 1',
                [$build, $chr, $start, $end, $ref, $alt]
            );

            if ($variantId === false) {
                $key = hash('sha256', json_encode(
                    [$build, $chr, (string)$start, (string)$end, $ref, $alt],
                    JSON_UNESCAPED_SLASHES
                ));

                $stmt = sql_run(
                    'INSERT INTO Variant
                     (GenomeBuild, Chromosome, MutationPosition, EndPosition,
                      ReferenceAllele, AlternateAllele, VariantKey)
                     VALUES (?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE VariantID = LAST_INSERT_ID(VariantID)',
                    [$build, $chr, $start, $end, $ref, $alt, $key]
                );
                $stmt->close();
                $variantId = (int)$conn->insert_id;
            }

            // Check whether the same observation has been saved already.
            // <=> treats two NULL ProteinChange values as equal in MySQL.
            $duplicate = query_value(
                'SELECT SampleMutationID FROM SampleMutation
                 WHERE SampleID = ? AND VariantID = ? AND GeneID = ?
                   AND MutationType = ? AND VariantType = ?
                   AND ProteinChange <=> ?
                 LIMIT 1',
                [$sampleId, $variantId, $geneId, $classification, $type, $protein]
            );

            if ($duplicate !== false) {
                $skipped++;
                continue;
            }

            // Only new observations reach this INSERT.
            $stmt = sql_run(
                'INSERT INTO SampleMutation
                 (SampleID, GeneID, VariantID, MutationType,
                  VariantType, Impact, ProteinChange, SourceRow)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $sampleId, $geneId, $variantId, $classification,
                    $type, $impact, $protein, $nextRow
                ]
            );
            $stmt->close();
            $nextRow++;
            $added++;
        }

        $conn->commit();
        return [
            'added' => $added,
            'skipped' => $skipped,
            'sample' => $sampleId,
            'newSample' => $newSample
        ];

    } catch (Throwable $problem) {
        // Rollback prevents partly uploaded mutation profiles.
        $conn->rollback();
        throw $problem;
    } finally {
        // Always release the MySQL advisory lock.
        query_value("SELECT RELEASE_LOCK('oncoatlas_mutation_import')");
    }
}

// STEP 3: Handle the submitted form, then show a success or error message.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // The hidden token protects against submitting a form from
        // another site; then we validate the chosen patient and sample.
        verify_csrf();
        $patientId=int_param($_POST['patient_id']??null);
        if(!$patientId || !query_value('SELECT PatientID FROM Patient WHERE PatientID=?',[$patientId]))throw new RuntimeException('Enter a valid patient ID.');
        $barcode=trim((string)($_POST['sample_barcode']??''));
        if($barcode===''||strlen($barcode)>100||preg_match('/[^A-Za-z0-9_.-]/',$barcode))throw new RuntimeException('Sample barcode must contain 1–100 characters: letters, numbers, periods, underscores and hyphens.');
        $type=trim((string)($_POST['sample_type']??'Primary'));
        if(!in_array($type,['Primary','Metastatic','Recurrent','Other'],true))throw new RuntimeException('Choose a valid sample type.');
        // First fully validate the TSV; then save its rows to MySQL.
        $rows=parse_mutation_upload($_FILES['mutation_file']??[],$barcode);
        $result=import_mutations($patientId,$barcode,$type,$rows);
        audit('IMPORT_MUTATIONS','TumorSample',(string)$result['sample']);
        flash('Import complete: '.$result['added'].' mutation records added; '.$result['skipped'].' duplicates skipped.');
        redirect('patient.php?id='.$patientId);
    } catch(Throwable $e){$err=$e instanceof mysqli_sql_exception?'Database error while importing. No partial mutation upload was committed.':$e->getMessage();error_log('Mutation import: '.$e->getMessage());audit('IMPORT_FAILED','TumorSample',null);}
}
// STEP 4: Display the same form again on GET or after a failed POST.
start_page('Import mutations','upload.php','Add a mutation profile to a patient’s existing or new tumour sample.','DATA MANAGEMENT');
?>
<div class="two-column form-layout">
<section class="panel">
<div class="panel-title">
<div>
<h2>Mutation profile upload</h2>
<p>Import a tab-delimited cBioPortal-like file for one sample at a time.</p>
</div>
</div>
<?php if($err):?>
<div class="notice error" role="alert">
<?=h($err)?>
</div>
<?php endif;?>
<form method="post" enctype="multipart/form-data" class="form-grid" id="uploadForm">
<?=csrf_field()?>
<div class="field">
<label for="uploadPatient">Patient ID *</label>
<input id="uploadPatient" type="number" name="patient_id" min="1" required value="<?=h($selectedPatient?:'')?>" placeholder="e.g. 1">
<small>Find the numeric ID in the patient explorer.</small>
</div>
<div class="field">
<label for="sampleBarcode">Sample barcode *</label>
<input id="sampleBarcode" name="sample_barcode" maxlength="100" required placeholder="TCGA-… or MAN-301-01" value="<?=h($_POST['sample_barcode']??'')?>">
<small>Use an existing barcode for that patient, or enter a new unique barcode.</small>
</div>
<div class="field">
<label for="sampleType">Sample type (for new barcodes)</label>
<select name="sample_type" id="sampleType">
<option>Primary</option>
<option>Metastatic</option>
<option>Recurrent</option>
<option>Other</option>
</select>
</div>
<div class="field">
<label>Selected patient / existing samples</label>
<div class="sample-lookup" id="sampleLookup" aria-live="polite">Enter a patient ID to see existing samples.</div>
</div>
<div class="field full">
<label for="mutationFile">TSV/TXT file *</label>
<input name="mutation_file" id="mutationFile" type="file" accept=".tsv,.txt,text/tab-separated-values,text/plain" required>
<small>Maximum 5 MB and 1,000 mutation rows. File must contain a header and a single sample barcode.</small>
</div>
<div class="form-actions">
<button type="submit" class="button primary">
<?=icon('upload')?> Import profile</button>
<a class="button subtle" href="patients.php">Cancel</a>
</div>
</form>
</section>
<aside class="panel aside-help">
<span class="metric-symbol teal">
<?=icon('shield')?>
</span>
<h3>Required file columns</h3>
<p>Your tab-delimited file must contain these exact column headers (in any order):</p>
<div class="required-fields">
<?php foreach(['Hugo_Symbol','NCBI_Build','Chromosome','Start_Position','End_Position','Reference_Allele','Tumor_Seq_Allele2','Variant_Classification','Variant_Type','Tumor_Sample_Barcode'] as $f):?>
<code>
<?=h($f)?>
</code>
<?php endforeach;?>
</div>
<h3>Optional annotations</h3>
<p>
<code>IMPACT</code> and <code>HGVSp_Short</code> are supported if present. Existing records remain unchanged, and duplicate observations are skipped during new imports.</p>
<p class="small-help">The import is transactional: invalid rows cancel the entire upload. This is for small demonstration profiles; full cohort ingestion is handled through your existing SQL dump.</p>
</aside>
</div>
<script>window.addEventListener('DOMContentLoaded',()=>window.OncoUpload?.init());</script>
<?php end_page();
