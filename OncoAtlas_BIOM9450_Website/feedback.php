<?php
require_once __DIR__.'/app/bootstrap.php';require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('predictions.php');
try {
    verify_csrf();
    $id=int_param($_POST['prediction_id']??null);
    if(!$id)throw new RuntimeException('Choose a valid prediction.');
    $p=query_one('SELECT PatientID FROM Prediction WHERE PredictionID=?',[$id]);
    if(!$p || !can_view_patient((int)$p['PatientID'])) forbidden();
    $raw=$_POST['accuracy']??'';
    if(!in_array($raw,['1','0','unknown'],true))throw new RuntimeException('Choose an assessment.');
    $isAccurate=$raw==='unknown'?null:(int)$raw;
    $comments=nullable_post('comments',1500);
    db()->prepare('INSERT INTO Feedback(PredictionID,UserID,IsAccurate,Comments) VALUES(?,?,?,?)')->execute([$id,app_user()['id'],$isAccurate,$comments]);
    audit('CREATE_FEEDBACK','Prediction',(string)$id);flash('Feedback saved against prediction #'.$id.'.');
    redirect('prediction.php?id='.$id);
} catch(Throwable $e) {
    error_log('Feedback error: '.$e->getMessage());flash($e instanceof PDOException?'Could not save feedback.':$e->getMessage(),'error');
    redirect('predictions.php');
}
