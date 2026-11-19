<?php
require_once __DIR__.'/app/layout.php';require_login();
if(account_role()==='Patient') {
  $id=linked_patient_id();
  start_page('My workspace','dashboard.php','Your linked research profile and future prediction history.');
  ?>
  <section class="hero-banner"><div><div class="overline">PATIENT ACCESS</div><h2>Welcome to your research workspace.</h2><p>Your account can view only its associated profile and predictions. Data in this demonstration is for education, not medical advice.</p><a class="button white" href="my_profile.php">View my profile <?=icon('arrow')?></a></div><div class="hero-orbit">✳</div></section>
  <section class="panel"><div class="panel-title"><div><h2>Account access</h2><p><?= $id ? 'Your profile is linked to this account.' : 'No patient record is linked to your account yet.' ?></p></div></div><div class="quick-links"><a href="my_profile.php" class="quick-link">My patient record <?=icon('arrow')?></a><a href="predictions.php" class="quick-link">Prediction history <?=icon('arrow')?></a></div></section>
  <?php end_page();exit;
}
$stats=query_one('SELECT (SELECT COUNT(*) FROM Patient) AS patients,(SELECT COUNT(*) FROM TumorSample) AS samples,(SELECT COUNT(*) FROM SampleMutation) AS mutations,(SELECT COUNT(*) FROM Gene) AS genes,(SELECT COUNT(*) FROM CancerType) AS cohorts');
$recent=query_rows('SELECT p.PatientID,p.TCGAPatientID,p.Name,c.StudyCode,c.DiagnosisLabel,p.Age,p.Sex, COUNT(s.SampleID) AS SampleCount FROM Patient p LEFT JOIN CancerType c ON p.CancerTypeID=c.CancerTypeID LEFT JOIN TumorSample s ON s.PatientID=p.PatientID GROUP BY p.PatientID,p.TCGAPatientID,p.Name,c.StudyCode,c.DiagnosisLabel,p.Age,p.Sex ORDER BY p.PatientID DESC LIMIT 6');
audit('VIEW_DASHBOARD','Dashboard',null);
start_page('Overview','dashboard.php','Explore de-identified genomic observations across six cancer cohorts.');
?>
<section class="hero-banner"><div class="hero-content"><div class="overline">CANCER GENOMICS · TCGA PANCANCER ATLAS</div><h2>Research, connected.</h2><p>A unified workspace to explore patient cohorts, identify genetic variants, and make sense of the data behind cancer research.</p><div class="hero-actions"><a class="button white" href="patients.php">Explore patients <?=icon('arrow')?></a><a class="button glass" href="mutations.php">Browse mutations</a></div></div><div class="hero-orbit"><span>✳</span></div></section>
<div class="metrics-row">
  <article class="metric-card"><span class="metric-symbol blue"><?=icon('users')?></span><span class="metric-caption">Patient records</span><strong><?=number_format((int)$stats['patients'])?></strong><small>Across all cohorts</small></article>
  <article class="metric-card"><span class="metric-symbol teal"><?=icon('layers')?></span><span class="metric-caption">Tumour samples</span><strong><?=number_format((int)$stats['samples'])?></strong><small>Linked to patients</small></article>
  <article class="metric-card"><span class="metric-symbol violet"><?=icon('dna')?></span><span class="metric-caption">Mutation records</span><strong><?=number_format((int)$stats['mutations'])?></strong><small>Genomic observations</small></article>
  <article class="metric-card"><span class="metric-symbol amber"><?=icon('spark')?></span><span class="metric-caption">Unique genes</span><strong><?=number_format((int)$stats['genes'])?></strong><small>Across <?=number_format((int)$stats['cohorts'])?> cohorts</small></article>
</div>
<div class="dashboard-grid">
<section class="panel"><div class="panel-title"><div><h2>Patients by cancer cohort</h2><p>Distribution of patient records across the six cancer groups</p></div><span class="mini-tag">DATA OVERVIEW</span></div><div id="cohortChart" class="chart-slot" data-chart="cohorts"><div class="skeleton-bars">Loading chart…</div></div></section>
<section class="panel"><div class="panel-title"><div><h2>Most observed genes</h2><p>Mutation record counts by gene symbol</p></div><span class="mini-tag">TOP GENES</span></div><div id="geneChart" class="chart-slot" data-chart="topGenes"><div class="skeleton-bars">Loading chart…</div></div></section>
</div>
<section class="panel"><div class="panel-title"><div><h2>Recently listed patients</h2><p>Open a record to explore its linked tumour samples and variants</p></div><a class="text-link" href="patients.php">View all patients <?=icon('arrow')?></a></div><div class="table-scroll"><table><thead><tr><th>Patient</th><th>Identifier</th><th>Cohort</th><th>Age</th><th>Sex</th><th>Samples</th><th></th></tr></thead><tbody>
<?php foreach($recent as $p):?><tr><td><strong><?=h($p['Name'])?></strong></td><td><span class="mono"><?=display($p['TCGAPatientID'])?></span></td><td><span class="code-pill"><?=h(strtoupper((string)$p['StudyCode']))?></span></td><td><?=display($p['Age'])?></td><td><?=display($p['Sex'])?></td><td><?=h($p['SampleCount'])?></td><td><a class="row-link" href="patient.php?id=<?=$p['PatientID']?>">View <?=icon('chevron')?></a></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<div class="footer-note">Patient names and demographic fields in this supplied sample database are synthetic; mutation observations derive from TCGA sources.</div>
<script>window.addEventListener('DOMContentLoaded',()=>window.OncoCharts?.loadOverview());</script>
<?php end_page();
