<?php
require_once __DIR__.'/../app/bootstrap.php';require_professional();require_get();
try {
    $scope=(string)($_GET['scope']??'overview');
    if(!in_array($scope,['overview','analytics','compare'],true))json_response(['error'=>'Unknown scope'],422);
    if(in_array($scope,['analytics','compare'],true)&&!is_researcher())json_response(['error'=>'Researcher access required'],403);
    $cohorts=query_rows('SELECT c.CancerName,c.StudyCode,COUNT(DISTINCT p.PatientID) AS Patients,COUNT(DISTINCT s.SampleID) AS Samples,COUNT(sm.SampleMutationID) AS Mutations FROM CancerType c LEFT JOIN Patient p ON p.CancerTypeID=c.CancerTypeID LEFT JOIN TumorSample s ON s.PatientID=p.PatientID LEFT JOIN SampleMutation sm ON sm.SampleID=s.SampleID GROUP BY c.CancerTypeID,c.CancerName,c.StudyCode ORDER BY c.CancerTypeID');
    if($scope==='compare'){
        $gene=trim((string)($_GET['gene']??''));if($gene==='' || strlen($gene)>100)json_response(['error'=>'Enter a gene symbol (maximum 100 characters)'],422);
        $rows=query_rows('SELECT c.CancerName,c.StudyCode,COUNT(sm.SampleMutationID) AS Mutations,COUNT(DISTINCT CASE WHEN sm.SampleMutationID IS NOT NULL THEN s.SampleID END) AS Samples FROM CancerType c LEFT JOIN Patient p ON p.CancerTypeID=c.CancerTypeID LEFT JOIN TumorSample s ON s.PatientID=p.PatientID LEFT JOIN SampleMutation sm ON sm.SampleID=s.SampleID AND sm.GeneID IN (SELECT GeneID FROM Gene WHERE GeneSymbol=?) GROUP BY c.CancerTypeID,c.CancerName,c.StudyCode ORDER BY c.CancerTypeID',[$gene]);
        json_response(['gene'=>$gene,'rows'=>$rows]);
    }
    if($scope==='overview'){
        $genes=query_rows('SELECT g.GeneSymbol,COUNT(sm.SampleMutationID) AS Mutations FROM Gene g JOIN SampleMutation sm ON sm.GeneID=g.GeneID GROUP BY g.GeneID,g.GeneSymbol ORDER BY Mutations DESC,g.GeneSymbol LIMIT 8');
        json_response(['cohorts'=>$cohorts,'topGenes'=>$genes]);
    }
    $classes=query_rows('SELECT MutationType,COUNT(*) AS Mutations FROM SampleMutation GROUP BY MutationType ORDER BY Mutations DESC LIMIT 8');
    json_response(['cohorts'=>$cohorts,'classes'=>$classes]);
} catch(Throwable $e){error_log('Stats API: '.$e->getMessage());json_response(['error'=>'Unable to load chart data'],500);}
