<?php
require_once __DIR__.'/../app/bootstrap.php';require_professional();require_get();
try {
    $q=trim((string)($_GET['q']??''));if(strlen($q)>100)json_response(['error'=>'Search too long'],422);
    $cancer=trim((string)($_GET['cancer']??''));$page=int_param($_GET['page']??'1',1,10000)??1;$limit=20;
    $params=[];$where=[];
    if($q!==''){$where[]='LOWER(g.GeneSymbol) LIKE ?';$params[]='%'.strtolower($q).'%';}
    if($cancer!==''){
        $cid=int_param($cancer,1,1000);if(!$cid)json_response(['error'=>'Invalid cancer'],422);
        // The filter is in the join condition to keep the gene table as the source of truth.
    }
    $cond=$where?' WHERE '.implode(' AND ',$where):'';
    if($cancer!=='') {
        $count=(int)query_value('SELECT COUNT(DISTINCT g.GeneID) FROM Gene g JOIN SampleMutation sm ON sm.GeneID=g.GeneID JOIN TumorSample s ON s.SampleID=sm.SampleID JOIN Patient p ON p.PatientID=s.PatientID'.$cond.($cond?' AND ':' WHERE ').'p.CancerTypeID=?',array_merge($params,[$cid]));
    } else {
        $count=(int)query_value('SELECT COUNT(*) FROM Gene g'.$cond,$params);
    }
    $pages=max(1,(int)ceil($count/$limit));$page=min($page,$pages);
    $join=' LEFT JOIN SampleMutation sm ON sm.GeneID=g.GeneID LEFT JOIN TumorSample s ON s.SampleID=sm.SampleID LEFT JOIN Patient p ON p.PatientID=s.PatientID';
    // Filter the mutation observations rather than the gene lookup, using an aggregate condition.
    $cid=$cancer!=='' ? (int)$cancer : null;
    $match=$cid===null?'sm.SampleMutationID IS NOT NULL':'p.CancerTypeID='.(int)$cid;
    $sql='SELECT g.GeneID,g.GeneSymbol,
     COUNT(CASE WHEN '.$match.' THEN sm.SampleMutationID END) AS MutationCount,
     COUNT(DISTINCT CASE WHEN '.$match.' THEN s.SampleID END) AS SampleCount,
     COUNT(DISTINCT CASE WHEN '.$match.' THEN p.PatientID END) AS PatientCount
     FROM Gene g'.$join.$cond.' GROUP BY g.GeneID,g.GeneSymbol'.($cid===null?'':' HAVING MutationCount>0').' ORDER BY MutationCount DESC,g.GeneSymbol LIMIT ? OFFSET ?';
    $st=db()->prepare($sql);foreach($params as $i=>$v)$st->bindValue($i+1,$v,PDO::PARAM_STR);
    $st->bindValue(count($params)+1,$limit,PDO::PARAM_INT);$st->bindValue(count($params)+2,($page-1)*$limit,PDO::PARAM_INT);$st->execute();
    json_response(['rows'=>$st->fetchAll(),'total'=>$count,'page'=>$page,'pages'=>$pages]);
} catch(Throwable $e) {error_log('Genes API: '.$e->getMessage());json_response(['error'=>'Could not load genes'],500);}
