<?php
/*
Model input API (AJAX GET); reads the ModelInput view.
Component Owner: Chris (z5417031)
Summary: Returns one patient's saved mutation rows as JSON so predict.js can run the model in the browser.

Professionals only; each request is recorded in the audit log (VIEW_MODEL_INPUT).

*/
require_once __DIR__.'/../app/bootstrap.php';require_professional();require_get();
$id=int_param($_GET['patient_id']??null);if(!$id)json_response(['error'=>'Invalid patient ID'],422);
try{
    $patient=query_one('SELECT p.PatientID,p.Name,p.TCGAPatientID,p.DemographicsSynthetic,c.StudyCode,c.CancerName,c.DiagnosisLabel FROM Patient p LEFT JOIN CancerType c ON c.CancerTypeID=p.CancerTypeID WHERE p.PatientID=?',[$id]);
    if(!$patient)json_response(['error'=>'No patient record with that ID'],404);
    $rows=query_rows('SELECT SampleMutationID,Tumor_Sample_Barcode,Hugo_Symbol,Variant_Classification,Variant_Type,Reference_Allele,Tumor_Seq_Allele2,Chromosome,Start_Position,End_Position FROM ModelInput WHERE PatientID=? ORDER BY SampleMutationID',[$id]);
    audit('VIEW_MODEL_INPUT','Patient',(string)$id);
    json_response(['patient'=>$patient,'rows'=>$rows]);
}catch(Throwable $e){error_log('Model input API: '.$e->getMessage());json_response(['error'=>'Unable to load the mutation profile'],500);}
