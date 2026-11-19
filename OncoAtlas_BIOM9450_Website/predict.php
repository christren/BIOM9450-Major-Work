<?php
/*
Run prediction page; loads predict.js and predict_page.js.
Component Owner: Chris (z5417031)
Summary: Lets professionals choose a patient (or preview a TSV file), run the cancer-type model and save the result.

Flow: choose patient -> load saved profile (AJAX) or preview a TSV -> predict.js runs model -> chart -> save (AJAX).

*/
require_once __DIR__.'/app/layout.php';require_professional();
$selected=int_param($_GET['patient_id']??null)??0;
$patients=query_rows('SELECT p.PatientID,p.Name,p.TCGAPatientID,c.StudyCode,COALESCE(m.n,0) AS Mutations FROM Patient p LEFT JOIN CancerType c ON c.CancerTypeID=p.CancerTypeID LEFT JOIN (SELECT s.PatientID,COUNT(*) AS n FROM SampleMutation sm JOIN TumorSample s ON s.SampleID=sm.SampleID GROUP BY s.PatientID) m ON m.PatientID=p.PatientID ORDER BY p.PatientID');
$modelFile=__DIR__.'/assets/model/rf_model.json';
$modelUrl='assets/model/rf_model.json?v='.(is_file($modelFile)?filemtime($modelFile):0);
audit('VIEW_PREDICT','Patient',$selected?(string)$selected:null);
start_page('Run prediction','predict.php','Predict the likely cancer type from a patient’s mutation profile using the trained random forest.','PREDICTIVE ANALYSIS');
?>
<link rel="stylesheet" href="assets/css/predict.css">
<div id="predictApp" data-model-url="<?=h($modelUrl)?>" data-csrf="<?=h(csrf_token())?>">
<div class="two-column form-layout">
  <section class="panel">
    <div class="panel-title"><div><h2>1. Choose a mutation profile</h2><p>Use a patient’s saved mutations, or preview a tab-delimited file.</p></div></div>
    <div class="form-grid">
      <div class="field full"><label for="predictPatient">Patient</label>
        <select id="predictPatient"><option value="">Select a patient…</option><?php foreach($patients as $p):?><option value="<?=$p['PatientID']?>"<?=$selected===(int)$p['PatientID']?' selected':''?><?=(int)$p['Mutations']===0?' disabled':''?>>#<?=$p['PatientID']?> · <?=h($p['Name'])?> · <?=h($p['TCGAPatientID']?:'manual profile')?> · <?=number_format((int)$p['Mutations'])?> mutations</option><?php endforeach;?></select>
        <small>Patients with no saved mutations are disabled. Add mutations with <a href="upload.php">Import mutations</a>.</small></div>
      <div class="form-actions"><button type="button" class="button primary" id="runSaved"><?=icon('spark')?> Predict from saved profile</button></div>
      <div class="field full predict-divider"><span>or</span></div>
      <div class="field full"><label for="predictFile">Preview a TSV/TXT mutation file (not saved)</label>
        <input id="predictFile" type="file" accept=".tsv,.txt,text/tab-separated-values,text/plain">
        <small>Needs the same columns as Import mutations. One patient per file. To save a prediction, import the file first, then predict from the saved profile.</small></div>
    </div>
    <div id="predictStatus" class="notice" role="status" hidden></div>
  </section>
  <aside class="panel aside-help" id="modelInfo">
    <h3>About the model</h3><p class="muted">Loading model…</p>
  </aside>
</div>

<section class="panel" id="predictResult" hidden>
  <div class="panel-title"><div><h2>2. Prediction result</h2><p id="resultSubtitle"></p></div><span class="code-pill" id="resultVersion"></span></div>
  <div class="predict-headline"><div><span class="overline">MOST LIKELY CANCER TYPE</span><strong id="resultLabel"></strong><span id="resultScore"></span></div><div id="resultChecks"></div></div>
  <div class="predict-toolbar" role="group" aria-label="Chart options">
    <button type="button" class="button subtle" data-sort="score" aria-pressed="true">Sort by score</button>
    <button type="button" class="button subtle" data-sort="name" aria-pressed="false">Sort A–Z</button>
  </div>
  <div id="scoreChart" class="pred-chart" aria-label="Model score for each cancer type"></div>
  <p class="small-help" id="chartHint">Hover or tap a bar for details.</p>
  <div class="two-column predict-details">
    <div><h3>Input used by the model</h3><dl class="detail-grid" id="inputSummary"></dl></div>
    <div><h3>Mutation spectrum</h3><div id="spectrumChart" class="pred-chart small"></div></div>
  </div>
  <div class="inline-warning"><strong>Educational research tool, not a diagnosis.</strong> Scores are averaged over the random forest’s decision trees (trained on 2,196 patients from 6 TCGA cohorts) and add up to 100%. They are not calibrated clinical probabilities, and the model can only choose between these six cancer types.</div>
  <div class="form-actions" id="saveActions" hidden>
    <button type="button" class="button primary" id="savePrediction"><?=icon('check')?> Save prediction &amp; give feedback</button>
    <small>The server recomputes the scores from the database and links the prediction to the exact mutation rows used.</small>
  </div>
</section>
</div>
<script src="assets/js/predict.js" defer></script>
<script src="assets/js/predict_page.js" defer></script>
<?php end_page();
