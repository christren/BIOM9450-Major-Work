<?php
// Patient details — PHP receives ?id=123 from the patient directory.
// Each query below demonstrates a relationship between MySQL tables.
require_once __DIR__ . '/app/layout.php';
require_login();

// 1. Check the patient ID and the signed-in user's permissions.
// Patient accounts may only open the patient linked to their own UserID.
$patientId = int_param($_GET['id'] ?? null);
if (!$patientId || !can_view_patient($patientId)) {
    http_response_code(403);
    exit('Patient record not available.');
}

// 2. Load the patient and their cancer type / assigned oncologist.
// LEFT JOIN keeps the patient visible even if a professional is unassigned.
$patientSql = 'SELECT p.*, c.StudyCode, c.CancerName,
                      c.DiagnosisLabel, u.Name AS OncologistName
               FROM Patient p
               LEFT JOIN CancerType c ON c.CancerTypeID = p.CancerTypeID
               LEFT JOIN Professional pr ON pr.ProfessionalID = p.OncologistID
               LEFT JOIN AppUser u ON u.UserID = pr.UserID
               WHERE p.PatientID = ?';
$p = query_one($patientSql, [$patientId]);

if (!$p) {
    http_response_code(404);
    exit('Patient not found.');
}

// 3. Load all tumour samples for this patient.
// GROUP BY lets SQL count how many mutation records each sample has.
$sampleSql = 'SELECT s.SampleID, s.TCGASampleBarcode,
                     s.SampleType, s.CollectionDate,
                     COUNT(sm.SampleMutationID) AS MutationCount
              FROM TumorSample s
              LEFT JOIN SampleMutation sm ON sm.SampleID = s.SampleID
              WHERE s.PatientID = ?
              GROUP BY s.SampleID, s.TCGASampleBarcode,
                       s.SampleType, s.CollectionDate
              ORDER BY s.SampleID';
$samples = query_rows($sampleSql, [$patientId]);

// 4. Find the eight most frequently observed gene symbols.
$geneSql = 'SELECT g.GeneSymbol, COUNT(*) AS total
            FROM SampleMutation sm
            JOIN Gene g ON g.GeneID = sm.GeneID
            JOIN TumorSample s ON s.SampleID = sm.SampleID
            WHERE s.PatientID = ?
            GROUP BY g.GeneID, g.GeneSymbol
            ORDER BY total DESC, g.GeneSymbol
            LIMIT 8';
$topGenes = query_rows($geneSql, [$patientId]);

// 5. Read the most recent mutation observations and their positions.
$mutationSql = 'SELECT sm.SampleMutationID, g.GeneSymbol,
                       sm.MutationType, sm.ProteinChange,
                       v.Chromosome, v.MutationPosition,
                       s.TCGASampleBarcode
                FROM SampleMutation sm
                JOIN TumorSample s ON s.SampleID = sm.SampleID
                JOIN Gene g ON g.GeneID = sm.GeneID
                JOIN Variant v ON v.VariantID = sm.VariantID
                WHERE s.PatientID = ?
                ORDER BY sm.SampleMutationID DESC
                LIMIT 10';
$recent = query_rows($mutationSql, [$patientId]);

// 6. Audit the page view; HTML below shows the retrieved rows.
$id = $patientId;
audit('VIEW_PATIENT', 'Patient', (string)$patientId);
start_page(
    $p['Name'],
    'patients.php',
    'Patient details, linked samples and observed genetic mutations.',
    'PATIENT PROFILE'
);
?>

<div class="toolbar">
<a class="backlink" href="<?=account_role()==='Patient'?'dashboard.php':'patients.php'?>">← <?=account_role()==='Patient'?'My workspace':'All patients'?>
</a>
<?php if(is_professional()):?>
<a class="button primary" href="upload.php?patient_id=<?=$id?>">
<?=icon('upload')?> Import mutations</a>
<a class="button" href="predict.php?patient_id=<?=$id?>">
<?=icon('spark')?> Run prediction</a>
<?php endif;?>
</div>
<section class="profile-hero panel">
<div class="profile-avatar">
<?php if(!empty($p['Photo']) && preg_match('/^assets\/uploads\/[a-f0-9]{32}\.(jpg|png|webp)$/',(string)$p['Photo'])):?>
<img src="<?=h($p['Photo'])?>" alt="Demonstration patient photo">
<?php else:?>
<?=h(strtoupper(substr($p['Name'],0,1)))?>
<?php endif;?>
</div>
<div>
<div class="overline">PATIENT IDENTIFIER · <?=h($p['TCGAPatientID']?:'MANUAL PROFILE')?>
</div>
<h2>
<?=h($p['Name'])?>
</h2>
<p>
<?=display($p['Diagnosis']?:$p['DiagnosisLabel'])?>
</p>
<div class="profile-tags">
<span class="code-pill">
<?=h(strtoupper((string)($p['StudyCode']??'UNCATEGORISED')))?>
</span>
<span class="chip <?= $p['DemographicsSynthetic']?'warning':'informative' ?>">
<?= $p['DemographicsSynthetic']?'Synthetic demographics':'Patient record' ?>
</span>
</div>
</div>
<div class="profile-stat">
<strong>
<?=count($samples)?>
</strong>
<small>Linked samples</small>
</div>
</section>
<div class="dashboard-grid profile-grid">
<section class="panel">
<div class="panel-title">
<div>
<h2>Clinical information</h2>
<p>Source-linked patient details</p>
</div>
</div>
<dl class="detail-grid">
<div>
<dt>Patient ID</dt>
<dd>
<?=$p['PatientID']?>
</dd>
</div>
<div>
<dt>TCGA barcode</dt>
<dd>
<?=display($p['TCGAPatientID'])?>
</dd>
</div>
<div>
<dt>Age</dt>
<dd>
<?=display($p['Age'])?>
</dd>
</div>
<div>
<dt>Sex</dt>
<dd>
<?=display($p['Sex'])?>
</dd>
</div>
<div>
<dt>Country</dt>
<dd>
<?=display($p['Country'])?>
</dd>
</div>
<div>
<dt>Assigned oncologist</dt>
<dd>
<?=display($p['OncologistName'])?>
</dd>
</div>
<div class="span-two">
<dt>Diagnosis</dt>
<dd>
<?=display($p['Diagnosis'])?>
</dd>
</div>
<div class="span-two">
<dt>Treatment</dt>
<dd>
<?=display($p['Treatment'])?>
</dd>
</div>
</dl>
<p class="inline-warning">The supplied database labels the patient demographics as synthetic. Do not interpret names, ages, country or treatment as verified TCGA clinical facts.</p>
</section>
<section class="panel">
<div class="panel-title">
<div>
<h2>Most observed genes</h2>
<p>Mutation observations across this patient's samples</p>
</div>
</div>
<?php if(!$topGenes):?>
<?php empty_state('No mutations recorded','No mutation observations have been linked to this patient.');?>
<?php else:$max=max(array_column($topGenes,'total'));?>
<div class="ranked-bars">
<?php foreach($topGenes as $g):?>
<div class="rank-row">
<span>
<?=h($g['GeneSymbol'])?>
</span>
<div class="rank-track">
<i style="width:<?=round(100*(int)$g['total']/$max)?>%">
</i>
</div>
<b>
<?=h($g['total'])?>
</b>
</div>
<?php endforeach;?>
</div>
<?php endif;?>
</section>
</div>
<section class="panel">
<div class="panel-title">
<div>
<h2>Linked tumour samples</h2>
<p>Samples are associated with this patient's record via a foreign key</p>
</div>
</div>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Sample barcode</th>
<th>Type</th>
<th>Collection date</th>
<th>Mutation records</th>
<th>
</th>
</tr>
</thead>
<tbody>
<?php foreach($samples as $s):?>
<tr>
<td class="mono">
<?=display($s['TCGASampleBarcode'])?>
</td>
<td>
<?=display($s['SampleType'])?>
</td>
<td>
<?=display($s['CollectionDate'])?>
</td>
<td>
<?=number_format((int)$s['MutationCount'])?>
</td>
<td>
<?php if(is_professional()):?>
<a class="row-link" href="mutations.php?sample=<?=urlencode((string)$s['TCGASampleBarcode'])?>">Browse <?=icon('chevron')?>
</a>
<?php endif;?>
</td>
</tr>
<?php endforeach;?>
<?php if(!$samples):?>
<tr>
<td colspan="5">No linked tumour samples.</td>
</tr>
<?php endif;?>
</tbody>
</table>
</div>
</section>
<section class="panel">
<div class="panel-title">
<div>
<h2>Recent mutation observations</h2>
<p>Newest records linked to this patient</p>
</div>
<?php if(is_professional()):?>
<a class="text-link" href="mutations.php?patient=<?=$id?>">View all <?=icon('arrow')?>
</a>
<?php endif;?>
</div>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Gene</th>
<th>Mutation classification</th>
<th>Protein change</th>
<th>Chromosome</th>
<th>Position</th>
<th>Sample</th>
</tr>
</thead>
<tbody>
<?php foreach($recent as $m):?>
<tr>
<td>
<strong>
<?=h($m['GeneSymbol'])?>
</strong>
</td>
<td>
<?=h($m['MutationType'])?>
</td>
<td class="mono">
<?=display($m['ProteinChange'])?>
</td>
<td>
<?=h($m['Chromosome'])?>
</td>
<td class="mono">
<?=h($m['MutationPosition'])?>
</td>
<td class="mono">
<?=display($m['TCGASampleBarcode'])?>
</td>
</tr>
<?php endforeach;?>
<?php if(!$recent):?>
<tr>
<td colspan="6">No mutation records available.</td>
</tr>
<?php endif;?>
</tbody>
</table>
</div>
</section>
<?php end_page();
