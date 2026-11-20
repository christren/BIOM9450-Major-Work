'use strict';
/* 
Run prediction page UI; uses OncoPredict from predict.js; no external libraries. 
Component Owner: Chris (z5417031)
Summary: Handles the user interface for running predictions using the OncoPredict model.

*/
(() => {
  const $ = (id) => document.getElementById(id);
  const app = $('predictApp');
  if (!app) return;
  const COLORS = {Blood: '#eb677d', Brain: '#6258e8', Breast: '#b56bd8', Liver: '#ef9a47', Pancreas: '#009b96', Prostate: '#4884dd'};
  const pct = (v) => (v * 100).toFixed(1) + '%';
  let model = null, trainSet = new Set(), current = null, sortMode = 'score';

  function el(tag, cls, text) {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text !== undefined) e.textContent = text;
    return e;
  }
  function status(message, type = 'error') {
    const s = $('predictStatus');
    s.hidden = !message; s.className = 'notice ' + type; s.textContent = message || '';
  }

  async function loadModel() {
    const res = await fetch(app.dataset.modelUrl, {credentials: 'same-origin'});
    if (!res.ok) throw new Error('Could not load the prediction model.');
    model = await res.json();
    trainSet = new Set(model.train_patients);
    const m = model.metrics, info = $('modelInfo');
    info.replaceChildren(el('h3', '', 'About the model'));
    const dl = el('dl', 'detail-grid');
    [['Model', 'Random forest, ' + model.trees.length + ' trees'], ['Version', model.model_version],
     ['Features', model.feature_names.length + ' (' + model.genes.length + ' genes + mutation spectrum)'],
     ['Held-out test accuracy', pct(m.test_accuracy)], ['Held-out macro-F1', m.test_macro_f1.toFixed(3)],
     ['Test patients', m.n_test_patients]].forEach(([k, v]) => {
      const d = el('div'); d.append(el('dt', '', k), el('dd', '', String(v))); dl.appendChild(d);
    });
    info.append(dl, el('p', 'small-help', 'Trained in Python (scikit-learn) on TCGA PanCancer Atlas mutations from 6 cohorts; '
      + 'tested on 549 patients never used for training. Classes: ' + model.classes.join(', ') + '.'));
  }

  function bars(container, items, {highlight, onHover} = {}) {
    container.replaceChildren();
    const max = Math.max(...items.map((i) => i.value), 1e-9);
    items.forEach((item) => {
      const row = el('div', 'pred-row' + (item.key === highlight ? ' top' : ''));
      row.tabIndex = 0;
      const track = el('div', 'pred-track'), bar = el('i');
      bar.style.width = (item.value / max * 100) + '%';
      bar.style.background = item.color || '#6258e8';
      track.appendChild(bar);
      row.append(el('span', 'pred-label', item.label), track, el('b', '', item.text));
      if (onHover) ['mouseenter', 'focus', 'click'].forEach((ev) => row.addEventListener(ev, () => onHover(item)));
      container.appendChild(row);
    });
    requestAnimationFrame(() => container.classList.add('ready'));
  }

  function drawScores() {
    const items = current.result.scores.map((s) => ({key: s.cancer, value: s.score, text: pct(s.score),
      label: s.cancer + ' (' + s.studyCode.toUpperCase() + ')', color: COLORS[s.cancer]}));
    if (sortMode === 'name') items.sort((a, b) => a.key.localeCompare(b.key));
    bars($('scoreChart'), items, {highlight: current.result.label, onHover: (i) => {
      $('chartHint').textContent = `${i.label}: score ${pct(i.value)} (average over the ${model.trees.length} decision trees of the share of similar training patients with this cancer type).`;
    }});
  }

  function chip(text, cls) { return el('span', 'chip ' + cls, text); }

  function render(result, source) {
    current = {result, source};
    const top = result.scores[0], second = result.scores[1];
    $('predictResult').hidden = false;
    $('resultLabel').textContent = top.cancer;
    $('resultLabel').style.color = COLORS[top.cancer];
    $('resultScore').textContent = ` score ${pct(top.score)} · next: ${second.cancer} ${pct(second.score)}`;
    $('resultVersion').textContent = result.modelVersion;
    $('resultSubtitle').textContent = source.title;

    const checks = $('resultChecks'); checks.replaceChildren();
    if (top.score - second.score < 0.1) checks.appendChild(chip('Low margin: top two scores are close', 'warning'));
    if (source.recorded) checks.appendChild(chip('Recorded diagnosis: ' + source.recorded + (source.recorded === top.cancer ? ' ✓ matches' : ' ✗ differs'),
      source.recorded === top.cancer ? 'informative' : 'warning'));
    if (source.tcgaId) checks.appendChild(trainSet.has(source.tcgaId)
      ? chip('Training patient: score will look better than on new data', 'warning')
      : chip('Held-out test patient (not used for training)', 'informative'));
    if (!result.info.modelGenesFound.length) checks.appendChild(chip('No model genes found: based on mutation spectrum only', 'warning'));

    const i = result.info, dl = $('inputSummary'); dl.replaceChildren();
    [['Rows submitted', i.rowsSubmitted], ['Distinct mutations used', i.mutationsUsed], ['Duplicates removed', i.duplicatesRemoved],
     ['Genes mutated', i.genesObserved], ['Model genes found', i.modelGenesFound.length + ' of ' + model.genes.length],
     ['Examples', i.modelGenesFound.slice(0, 8).join(', ') || 'none']].forEach(([k, v]) => {
      const d = el('div'); d.append(el('dt', '', k), el('dd', '', String(v))); dl.appendChild(d);
    });
    bars($('spectrumChart'), model.subs.map((s) => ({key: s, label: s, value: i.subCounts[s] / i.mutationsUsed,
      text: pct(i.subCounts[s] / i.mutationsUsed), color: '#6f83a6'})));
    drawScores();
    $('saveActions').hidden = source.type !== 'saved';
    $('predictResult').scrollIntoView({behavior: 'smooth', block: 'start'});
  }

  async function runSaved() {
    const id = $('predictPatient').value;
    if (!id) return status('Choose a patient first.');
    status('Loading mutation profile…', 'success');
    try {
      const res = await fetch('api/model_input.php?patient_id=' + encodeURIComponent(id), {credentials: 'same-origin'});
      if (res.status === 401) { window.location.href = 'login.php'; return; }
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Could not load the profile.');
      if (!data.rows.length) throw new Error('This patient has no saved mutations. Import a mutation file first.');
      const rows = data.rows.map((r) => Object.fromEntries(Object.entries(r).map(([k, v]) => [k, v === null ? '' : String(v)])));
      const p = data.patient;
      render(OncoPredict.predict(model, rows), {type: 'saved', patientId: p.PatientID, tcgaId: p.TCGAPatientID, recorded: p.CancerName,
        title: `Patient #${p.PatientID} · ${p.Name} · ${p.TCGAPatientID || 'manual profile'} · saved profile`});
      status('');
    } catch (e) { status(e.message); }
  }

  function runFile(file) {
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) return status('File is larger than 5 MB.');
    if (!/\.(tsv|txt)$/i.test(file.name)) return status('Choose a .tsv or .txt file.');
    const reader = new FileReader();
    reader.onload = () => {
      try {
        const groups = OncoPredict.groupByPatient(OncoPredict.parseTSV(reader.result, model.required_columns));
        if (groups.size > 1) throw new Error(`This file contains ${groups.size} patients (${[...groups.keys()].slice(0, 5).join(', ')}). Upload one patient at a time.`);
        const [patientKey, rows] = [...groups][0];
        render(OncoPredict.predict(model, rows), {type: 'file', tcgaId: patientKey.startsWith('TCGA-') ? patientKey : null,
          title: `File preview · ${file.name} · ${patientKey} · not saved`});
        status('Preview only. Import this file on the patient’s record to save a prediction.', 'success');
      } catch (e) { $('predictResult').hidden = true; status(e.message); }
    };
    reader.onerror = () => status('Could not read the file.');
    reader.readAsText(file);
  }

  async function save() {
    const btn = $('savePrediction');
    btn.disabled = true;
    const body = new FormData();
    body.append('csrf_token', app.dataset.csrf);
    body.append('patient_id', current.source.patientId);
    body.append('model_version', model.model_version);
    body.append('browser_scores', JSON.stringify(Object.fromEntries(current.result.scores.map((s) => [s.cancer, s.score]))));
    try {
      const res = await fetch('api/save_prediction.php', {method: 'POST', body, credentials: 'same-origin'});
      if (res.status === 401) { window.location.href = 'login.php'; return; }
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Could not save the prediction.');
      window.location.href = data.url;
    } catch (e) { status(e.message); btn.disabled = false; }
  }

  document.querySelectorAll('[data-sort]').forEach((b) => b.addEventListener('click', () => {
    sortMode = b.dataset.sort;
    document.querySelectorAll('[data-sort]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
    if (current) drawScores();
  }));
  $('runSaved').addEventListener('click', runSaved);
  $('predictFile').addEventListener('change', (e) => runFile(e.target.files[0]));
  $('savePrediction').addEventListener('click', save);
  loadModel().then(() => { if ($('predictPatient').value) runSaved(); }).catch((e) => status(e.message));
})();
