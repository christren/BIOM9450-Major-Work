<?php
// Cancer cohorts: use GROUP BY and SQL JOINs to calculate patient, sample and mutation totals for each of the six cancer types.

require_once __DIR__.'/app/layout.php';require_professional();audit('VIEW_COHORTS','CancerType',null);
$cohorts=query_rows('SELECT c.CancerTypeID,c.CancerName,c.StudyCode,c.DiagnosisLabel,COUNT(DISTINCT p.PatientID) AS PatientCount,COUNT(DISTINCT s.SampleID) AS SampleCount,COUNT(sm.SampleMutationID) AS MutationCount FROM CancerType c LEFT JOIN Patient p ON p.CancerTypeID=c.CancerTypeID LEFT JOIN TumorSample s ON s.PatientID=p.PatientID LEFT JOIN SampleMutation sm ON sm.SampleID=s.SampleID GROUP BY c.CancerTypeID,c.CancerName,c.StudyCode,c.DiagnosisLabel ORDER BY c.CancerTypeID');
start_page('Cancer cohorts','cohorts.php','Explore the six TCGA PanCancer Atlas cancer categories in the database.','CANCER RESEARCH');
?><div class="cohort-grid">
<?php foreach($cohorts as $c):?>
<article class="cohort-card">
<div class="cohort-heading">
<span class="cohort-icon">
<?=icon('layers')?>
</span>
<span class="code-pill">
<?=h(strtoupper($c['StudyCode']))?>
</span>
</div>
<h2>
<?=h($c['CancerName'])?>
</h2>
<p>
<?=h($c['DiagnosisLabel'])?>
</p>
<div class="cohort-metrics">
<div>
<b>
<?=number_format((int)$c['PatientCount'])?>
</b>
<small>Patients</small>
</div>
<div>
<b>
<?=number_format((int)$c['SampleCount'])?>
</b>
<small>Samples</small>
</div>
<div>
<b>
<?=number_format((int)$c['MutationCount'])?>
</b>
<small>Mutations</small>
</div>
</div>
<div class="cohort-links">
<a href="patients.php?cancer=<?=$c['CancerTypeID']?>">Patients <?=icon('arrow')?>
</a>
<a href="mutations.php?cancer=<?=$c['CancerTypeID']?>">Mutations <?=icon('arrow')?>
</a>
</div>
</article>
<?php endforeach;?>
</div>
<div class="footer-note">Counts represent records in your provided sample database, not the complete TCGA cohort population. Patient demographic information is synthetic.</div>
<?php end_page();
