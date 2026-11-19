<?php
require_once __DIR__.'/../app/bootstrap.php';require_professional();require_get();
try {
    $page=int_param($_GET['page']??'1',1,10000)??1;$limit=25;
    $params=[];$conditions=[];
    $gene=trim((string)($_GET['gene']??''));if(strlen($gene)>100)json_response(['error'=>'Gene filter too long'],422);
    if($gene!==''){$conditions[]='LOWER(g.GeneSymbol) LIKE ?';$params[]='%'.strtolower($gene).'%';}
    $cancer=trim((string)($_GET['cancer']??''));if($cancer!==''){$c=int_param($cancer,1,1000);if(!$c)json_response(['error'=>'Invalid cancer filter'],422);$conditions[]='p.CancerTypeID=?';$params[]=$c;}
    $chr=trim((string)($_GET['chr']??''));if(strlen($chr)>20)json_response(['error'=>'Chromosome filter too long'],422);
    if($chr!==''){$conditions[]='v.Chromosome=?';$params[]=$chr;}
    $pos=trim((string)($_GET['position']??''));if($pos!==''){
        if(!preg_match('/^[0-9]{1,18}$/',$pos)||$pos==='0')json_response(['error'=>'Position must be a positive integer'],422);
        $conditions[]='v.MutationPosition=?';$params[]=$pos;
    }
    $type=trim((string)($_GET['type']??''));if(strlen($type)>100)json_response(['error'=>'Invalid classification'],422);
    if($type!==''){$conditions[]='sm.MutationType=?';$params[]=$type;}
    $sample=trim((string)($_GET['sample']??''));if(strlen($sample)>100)json_response(['error'=>'Invalid sample'],422);
    if($sample!==''){$conditions[]='LOWER(s.TCGASampleBarcode) LIKE ?';$params[]='%'.strtolower($sample).'%';}
    $patient=trim((string)($_GET['patient']??''));if($patient!==''){$id=int_param($patient);if(!$id)json_response(['error'=>'Invalid patient'],422);$conditions[]='p.PatientID=?';$params[]=$id;}
    $from=' FROM SampleMutation sm JOIN TumorSample s ON s.SampleID=sm.SampleID JOIN Patient p ON p.PatientID=s.PatientID JOIN Gene g ON g.GeneID=sm.GeneID JOIN Variant v ON v.VariantID=sm.VariantID LEFT JOIN CancerType c ON c.CancerTypeID=p.CancerTypeID';
    $cond=$conditions?' WHERE '.implode(' AND ',$conditions):'';
    $count=(int)query_value('SELECT COUNT(*)'.$from.$cond,$params);$pages=max(1,(int)ceil($count/$limit));$page=min($page,$pages);
    $st=db()->prepare('SELECT sm.SampleMutationID,g.GeneSymbol,sm.MutationType,sm.VariantType,sm.Impact,sm.ProteinChange,v.GenomeBuild,v.Chromosome,v.MutationPosition,v.EndPosition,v.ReferenceAllele,v.AlternateAllele,s.TCGASampleBarcode,p.PatientID,c.StudyCode'.$from.$cond.' ORDER BY sm.SampleMutationID DESC LIMIT ? OFFSET ?');
    foreach($params as $i=>$val)$st->bindValue($i+1,$val,is_int($val)?PDO::PARAM_INT:PDO::PARAM_STR);
    $st->bindValue(count($params)+1,$limit,PDO::PARAM_INT);$st->bindValue(count($params)+2,($page-1)*$limit,PDO::PARAM_INT);$st->execute();
    json_response(['rows'=>$st->fetchAll(),'total'=>$count,'page'=>$page,'pages'=>$pages]);
} catch(Throwable $e) {error_log('Mutation API: '.$e->getMessage());json_response(['error'=>'Could not load mutation records'],500);}
