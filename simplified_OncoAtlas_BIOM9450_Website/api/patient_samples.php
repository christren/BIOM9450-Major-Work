<?php
// AJAX lookup for the Import mutations page.
// When a user enters PatientID, JavaScript fetches this endpoint.
// It returns one patient and that patient's existing tumour samples.
require_once __DIR__ . '/../app/bootstrap.php';
require_professional();
require_get();

$patientId = int_param($_GET['patient_id'] ?? null);

if (!$patientId) {
    json_response(['error' => 'Invalid patient ID'], 422);
}

try {
    // Prepared SELECT to find one patient by their primary key.
    $patient = query_one(
        'SELECT PatientID, Name, TCGAPatientID
         FROM Patient
         WHERE PatientID = ?',
        [$patientId]
    );

    if (!$patient) {
        json_response(['error' => 'No patient record with that ID'], 404);
    }

    // One patient can be linked to many tumour samples.
    $samples = query_rows(
        'SELECT SampleID, TCGASampleBarcode, SampleType
         FROM TumorSample
         WHERE PatientID = ?
         ORDER BY SampleID',
        [$patientId]
    );

    // Output fields must match assets/js/app.js (getJSON).
    json_response(['patient' => $patient, 'samples' => $samples]);

} catch (Throwable $error) {
    error_log('Patient samples API: ' . $error->getMessage());
    json_response(['error' => 'Unable to load samples'], 500);
}
