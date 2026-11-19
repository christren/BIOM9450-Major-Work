<?php
require_once __DIR__.'/../app/bootstrap.php';require_professional();require_get();
try {
    $q=trim((string)($_GET['q']??''));if(strlen($q)>100)json_response(['error'=>'Search is too long'],422);
    $cancer=trim((string)($_GET['cancer']??''));$sex=trim((string)($_GET['sex']??''));
    $page=int_param($_GET['page']??'1',1,10000)??1;$limit=20;$params=[];$where=[];
    if($q!==''){$where[]='(LOWER(p.Name) LIKE ? OR LOWER(p.TCGAPatientID) LIKE ?)';$params[]='%'.strtolower($q).'%';$params[]='%'.strtolower($q).'%';}
    if($cancer!==''){$id=int_param($cancer,1,1000);if(!$id)json_response(['error'=>'Invalid cohort'],422);$where[]='p.CancerTypeID=?';$params[]=$id;}
    if($sex!==''){if(!in_array($sex,['Male','Female'],true))json_response(['error'=>'Invalid sex filter'],422);$where[]='p.Sex=?';$params[]=$sex;}
    $cond=$where ? ' WHERE '.implode(' AND ',$where) : '';
    $total=(int)query_value('SELECT COUNT(*) FROM Patient p'.$cond,$params);
    $pages=max(1,(int)ceil($total/$limit));$page=min($pages,$page);$offset=($page-1)*$limit;
    $sql='SELECT p.PatientID,p.TCGAPatientID,p.Name,p.Age,p.Sex,p.DemographicsSynthetic,c.StudyCode,c.DiagnosisLabel,
      (SELECT COUNT(*) FROM TumorSample s WHERE s.PatientID=p.PatientID) AS Samples,
      (SELECT COUNT(*) FROM SampleMutation sm JOIN TumorSample s2 ON s2.SampleID=sm.SampleID WHERE s2.PatientID=p.PatientID) AS Mutations
      FROM Patient p LEFT JOIN CancerType c ON c.CancerTypeID=p.CancerTypeID'.$cond.' ORDER BY p.PatientID DESC LIMIT ? OFFSET ?';
    $st=db()->prepare($sql);foreach($params as $i=>$val)$st->bindValue($i+1,$val,is_int($val)?PDO::PARAM_INT:PDO::PARAM_STR);
    $st->bindValue(count($params)+1,$limit,PDO::PARAM_INT);$st->bindValue(count($params)+2,$offset,PDO::PARAM_INT);$st->execute();
    json_response(['rows'=>$st->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>$pages]);
} catch(Throwable $e) {error_log('Patients API: '.$e->getMessage());json_response(['error'=>'Could not load patient records'],500);}
