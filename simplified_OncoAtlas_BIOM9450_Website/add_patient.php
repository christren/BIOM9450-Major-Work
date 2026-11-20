<?php
// Add a patient
// This page contains both the HTML form and the PHP POST handling.
require_once __DIR__ . '/app/layout.php';
require_professional();

// These variables display messages and fill the dropdowns.
$error = '';
$cohorts = query_rows(
    'SELECT CancerTypeID, StudyCode, DiagnosisLabel
     FROM CancerType ORDER BY CancerTypeID'
);
$doctors = query_rows(
    "SELECT p.ProfessionalID, u.Name, p.Specialty
     FROM Professional p
     JOIN AppUser u ON u.UserID = p.UserID
     WHERE u.Role = 'Oncologist'
     ORDER BY u.Name"
);

// Only save the form if the user clicked Create patient (POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $photoPath = null;

    try {
        verify_csrf();

        // 1. Read and validate the patient information.
        $name = nullable_post('name', 100);
        if (!$name) {
            throw new RuntimeException('Please enter a display name.');
        }

        $cancer = int_param($_POST['cancer'] ?? null);
        if (!$cancer || !query_value(
            'SELECT CancerTypeID FROM CancerType WHERE CancerTypeID = ?',
            [$cancer]
        )) {
            throw new RuntimeException('Choose a valid cancer type.');
        }

        // Age is optional, but must be a whole number from 0 to 120.
        $ageText = trim((string)($_POST['age'] ?? ''));
        $age = $ageText === '' ? null : int_param($ageText, 0, 120);
        if ($ageText !== '' && $age === null) {
            throw new RuntimeException('Age must be a whole number from 0 to 120.');
        }

        $sex = nullable_post('sex', 20);
        $validSex = ['Male', 'Female', 'Other', 'Unknown'];
        if ($sex !== null && !in_array($sex, $validSex, true)) {
            throw new RuntimeException('Choose a valid sex.');
        }

        $country = nullable_post('country', 100);
        $treatment = nullable_post('treatment', 255);
        $diagnosis = nullable_post('diagnosis', 255);

        // Assigned oncologist is optional.
        $doctorText = trim((string)($_POST['oncologist'] ?? ''));
        $oncologist = $doctorText === '' ? null : int_param($doctorText);
        if ($doctorText !== '' && $oncologist === null) {
            throw new RuntimeException('Choose a valid oncologist.');
        }
        if ($oncologist !== null && !query_value(
            'SELECT ProfessionalID FROM Professional WHERE ProfessionalID = ?',
            [$oncologist]
        )) {
            throw new RuntimeException('Choose a valid oncologist.');
        }

        // 2. Optional profile photo 
        if (isset($_FILES['photo'])
            && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {

            $file = $_FILES['photo'];

            if ($file['error'] !== UPLOAD_ERR_OK
                || $file['size'] <= 0
                || $file['size'] > 2 * 1024 * 1024
                || !is_uploaded_file($file['tmp_name'])) {
                throw new RuntimeException('Photo must be a valid image up to 2 MB.');
            }

            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowed[$mimeType]) || !getimagesize($file['tmp_name'])) {
                throw new RuntimeException('Upload a JPG, PNG or WebP image.');
            }

            // Generate a random filename so two photos cannot overwrite one another.
            $photoPath = 'assets/uploads/' . bin2hex(random_bytes(16))
                       . '.' . $allowed[$mimeType];

            if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $photoPath)) {
                throw new RuntimeException('Could not save photo. Check assets/uploads permissions.');
            }
        }

        // 3. Save the new record with a prepared INSERT statement.
        // TCGAPatientID is NULL: we never invent an official TCGA patient ID.
        // DemographicsSynthetic = 1 distinguishes this demo data.
        $sql = 'INSERT INTO Patient
                (TCGAPatientID, Name, Photo, Age, Sex, Country, Treatment,
                 Diagnosis, CancerTypeID, DemographicsSynthetic, UserID, OncologistID)
                VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, 1, NULL, ?)';

        $stmt = sql_run($sql, [
            $name, $photoPath, $age, $sex, $country, $treatment,
            $diagnosis, $cancer, $oncologist
        ]);
        $stmt->close();

        // insert_id returns the automatically created PatientID.
        $newPatientId = (int)mysqli_db()->insert_id;
        audit('CREATE_PATIENT', 'Patient', (string)$newPatientId);
        flash('The demonstration patient profile was created.');
        redirect('patient.php?id=' . $newPatientId);

    } catch (Throwable $problem) {
        // If SQL failed, remove any newly uploaded photo.
        if ($photoPath !== null && file_exists(__DIR__ . '/' . $photoPath)) {
            unlink(__DIR__ . '/' . $photoPath);
        }

        error_log('Create patient: ' . $problem->getMessage());
        if ($problem instanceof mysqli_sql_exception) {
            $error = 'Could not save the patient. Please review the fields.';
        } else {
            $error = $problem->getMessage();
        }
    }
}

// 4. Show the HTML form, whether on first visit or after an invalid POST.
start_page(
    'Add a patient',
    'patients.php',
    'Create a clearly labelled demonstration profile without modifying any imported TCGA records.',
    'CLINICAL RECORDS'
);
?>

<div class="backlink">
<a href="patients.php">← Back to patient explorer</a>
</div>
<div class="two-column form-layout">
<section class="panel">
<div class="panel-title">
<div>
<h2>Patient information</h2>
<p>Fields marked * are required.</p>
</div>
</div>
<?php if($error):?>
<div class="notice error" role="alert">
<?=h($error)?>
</div>
<?php endif;?>
<form method="post" enctype="multipart/form-data" class="form-grid">
<?=csrf_field()?>
<div class="field">
<label for="name">Display name *</label>
<input id="name" name="name" maxlength="100" required value="<?=h($_POST['name']??'')?>" placeholder="e.g. Demo Patient 301">
</div>
<div class="field">
<label for="cancer">Cancer cohort *</label>
<select id="cancer" name="cancer" required>
<option value="">Select a cancer cohort</option>
<?php foreach($cohorts as $c):?>
<option value="<?=$c['CancerTypeID']?>" <?=($_POST['cancer']??'')==$c['CancerTypeID']?'selected':''?>>
<?=h(strtoupper($c['StudyCode']).' — '.$c['DiagnosisLabel'])?>
</option>
<?php endforeach;?>
</select>
</div>
<div class="field">
<label for="age">Age at diagnosis</label>
<input id="age" name="age" type="number" min="0" max="120" step="1" value="<?=h($_POST['age']??'')?>">
</div>
<div class="field">
<label for="sex">Sex</label>
<select id="sex" name="sex">
<option value="">Not specified</option>
<?php foreach(['Female','Male','Other','Unknown'] as $option):?>
<option <?=($_POST['sex']??'')===$option?'selected':''?>>
<?=h($option)?>
</option>
<?php endforeach;?>
</select>
</div>
<div class="field">
<label for="country">Country (demonstration)</label>
<input id="country" name="country" maxlength="100" value="<?=h($_POST['country']??'')?>">
</div>
<div class="field">
<label for="oncologist">Assigned oncologist</label>
<select name="oncologist" id="oncologist">
<option value="">Unassigned</option>
<?php foreach($doctors as $d):?>
<option value="<?=$d['ProfessionalID']?>">
<?=h($d['Name'])?>
</option>
<?php endforeach;?>
</select>
</div>
<div class="field full">
<label for="photo">Profile photo (optional)</label>
<input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
<small>JPG, PNG or WebP; maximum 2 MB. For fictional demonstration profiles only.</small>
</div>
<div class="field full">
<label for="diagnosis">Diagnosis notes</label>
<input name="diagnosis" id="diagnosis" maxlength="255" placeholder="For demo purposes only" value="<?=h($_POST['diagnosis']??'')?>">
</div>
<div class="field full">
<label for="treatment">Treatment notes</label>
<textarea id="treatment" name="treatment" rows="3" maxlength="255" placeholder="Optional teaching note">
<?=h($_POST['treatment']??'')?>
</textarea>
</div>
<div class="form-actions">
<button class="button primary" type="submit">
<?=icon('plus')?> Create patient</button>
<a class="button subtle" href="patients.php">Cancel</a>
</div>
</form>
</section>
<aside class="panel aside-help">
<span class="metric-symbol teal">
<?=icon('shield')?>
</span>
<h3>Research-data safeguards</h3>
<p>New profiles are labelled as synthetic demonstration data. Their TCGA identifier remains empty to avoid suggesting that the record exists in the TCGA studies.</p>
<p>To attach a genomic profile after creating the patient, open the record and use <strong>Import mutations</strong>.</p>
</aside>
</div>
<?php end_page();
