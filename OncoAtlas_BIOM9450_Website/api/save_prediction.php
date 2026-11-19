<?php
/*
Save prediction API (AJAX POST); uses app/model_predictor.php.
Component Owner: Chris (z5417031)
Summary: Recomputes the prediction on the server from the patient's ModelInput rows, checks it agrees with the browser result, then saves it in one transaction.

Scores sent by the browser are only compared, never stored. Rejections are audited (PREDICTION_REJECTED).

*/
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/model_predictor.php';
require_professional();
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['error'=>'POST required'],405);
try{verify_csrf();}catch(RuntimeException $e){json_response(['error'=>$e->getMessage()],403);}
$id=int_param($_POST['patient_id']??null);
if(!$id)json_response(['error'=>'Choose a valid patient.'],422);
if(!can_view_patient($id))forbidden();
try{
    if(!query_value('SELECT PatientID FROM Patient WHERE PatientID=?',[$id]))json_response(['error'=>'No patient record with that ID'],404);
    $rows=query_rows('SELECT SampleMutationID,Tumor_Sample_Barcode,Hugo_Symbol,Variant_Classification,Variant_Type,Reference_Allele,Tumor_Seq_Allele2,Chromosome,Start_Position,End_Position FROM ModelInput WHERE PatientID=? ORDER BY SampleMutationID',[$id]);
    if(!$rows)json_response(['error'=>'This patient has no saved mutation profile. Import mutations first.'],422);
    $p=model_predict($rows);
    $reject=function(string $message) use($id): never {audit('PREDICTION_REJECTED','Patient',(string)$id);json_response(['error'=>$message],409);};
    if(($_POST['model_version']??'')!==$p['model_version'])$reject('The page used a different model version. Reload the page and try again.');
    $browser=json_decode((string)($_POST['browser_scores']??''),true);
    if(!is_array($browser))json_response(['error'=>'Missing browser prediction.'],422);
    foreach($p['scores'] as $cls=>$s){
        if(!isset($browser[$cls]) || !is_numeric($browser[$cls]) || abs((float)$browser[$cls]-$s)>1e-4)
            $reject('Browser and server predictions disagree, so nothing was saved. Reload the page and try again.');
    }
    $predictionId=prediction_save(db(),$id,(int)app_user()['id'],$p,array_column($rows,'SampleMutationID'));
    audit('CREATE_PREDICTION','Prediction',(string)$predictionId);
    json_response(['prediction_id'=>$predictionId,'url'=>'prediction.php?id='.$predictionId,'label'=>$p['label'],'linked_mutations'=>count($rows)]);
}catch(Throwable $e){
    error_log('Save prediction: '.$e->getMessage());audit('PREDICTION_FAILED','Patient',(string)$id);
    json_response(['error'=>'Could not save the prediction. Nothing was stored.'],500);
}
