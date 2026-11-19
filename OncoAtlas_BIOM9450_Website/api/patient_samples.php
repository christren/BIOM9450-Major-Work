<?php
require_once __DIR__.'/../app/bootstrap.php';require_professional();require_get();
$id=int_param($_GET['patient_id']??null);if(!$id)json_response(['error'=>'Invalid patient ID'],422);
try{
 $patient=query_one('SELECT PatientID,Name,TCGAPatientID FROM Patient WHERE PatientID=?',[$id]);
 if(!$patient)json_response(['error'=>'No patient record with that ID'],404);
 $samples=query_rows('SELECT SampleID,TCGASampleBarcode,SampleType FROM TumorSample WHERE PatientID=? ORDER BY SampleID',[$id]);
 json_response(['patient'=>$patient,'samples'=>$samples]);
}catch(Throwable $e){error_log('Patient samples API: '.$e->getMessage());json_response(['error'=>'Unable to load samples'],500);}
