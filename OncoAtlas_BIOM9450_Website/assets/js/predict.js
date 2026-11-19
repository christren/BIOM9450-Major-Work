'use strict';
/*

OncoPredict: runs the exported random forest (assets/model/rf_model.json) in the browser
Component Owner: Chris (z5417031) 
Summary: Runs the exported random forest model in the browser or Node, ensuring parity with the Python and PHP implementations. 

Works in the browser (window.OncoPredict) and in Node (require) for testing.

*/
(function (root, factory) {
  if (typeof module === 'object' && module.exports) module.exports = factory();
  else root.OncoPredict = factory();
})(typeof self !== 'undefined' ? self : this, function () {
  const COMP = {A: 'T', T: 'A', C: 'G', G: 'C'};
  const MAX_ROWS = 20000;

  // Tab-delimited text -> array of row objects. Throws Error with a user-friendly message on invalid input.
  function parseTSV(text, requiredColumns) {
    const lines = String(text ?? '').replace(/^\uFEFF/, '').split(/\r?\n/).filter((l) => l.trim() !== '' && !l.startsWith('#'));
    if (!lines.length) throw new Error('The file is empty.');
    const header = lines[0].split('\t').map((h) => h.trim());
    if (new Set(header).size !== header.length) throw new Error('Duplicate column headers found.');
    const missing = requiredColumns.filter((c) => !header.includes(c));
    if (missing.length) throw new Error('Required columns missing: ' + missing.join(', ') + '. Is the file tab-delimited?');
    if (lines.length === 1) throw new Error('The file has a header but no mutation rows.');
    if (lines.length - 1 > MAX_ROWS) throw new Error(`Too many rows (maximum ${MAX_ROWS}).`);
    return lines.slice(1).map((line, i) => {
      const values = line.split('\t');
      if (values.length !== header.length) throw new Error(`Line ${i + 2} has ${values.length} columns; the header has ${header.length}.`);
      const row = {};
      header.forEach((h, j) => { row[h] = values[j].trim(); });
      return row;
    });
  }

  // Same rule as training: the first 12 characters of the sample barcode identify a patient (TCGA-XX-YYYY).
  function groupByPatient(rows) {
    const groups = new Map();
    rows.forEach((r) => {
      const key = String(r.Tumor_Sample_Barcode ?? '').slice(0, 12);
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key).push(r);
    });
    return groups;
  }

  function substitutionClass(r) {
    const ref = r.Reference_Allele, alt = r.Tumor_Seq_Allele2, type = r.Variant_Type;
    if (type === 'SNP' && ref in COMP && alt in COMP) {
      const flip = ref === 'A' || ref === 'G';                 // express every SNP on the C/T strand
      return (flip ? COMP[ref] : ref) + '>' + (flip ? COMP[alt] : alt);
    }
    return type === 'INS' || type === 'DEL' ? 'indel' : 'other';
  }

  // Rows for ONE patient -> feature vector in model.feature_names order (float32-rounded, as sklearn sees it).
  function buildFeatures(model, rows) {
    const seen = new Set(), kept = [];
    rows.forEach((r) => {                                       // de-duplicate by genomic identity, keep first
      const key = model.dedup_key.map((c) => String(r[c] ?? '')).join('\u0001');
      if (!seen.has(key)) { seen.add(key); kept.push(r); }
    });
    const n = kept.length;
    if (n === 0) throw new Error('No mutations to analyse.');
    const knownVC = new Set(model.variant_classes.slice(0, -1));
    const subCounts = Object.fromEntries(model.subs.map((s) => [s, 0]));
    const vcCounts = Object.fromEntries(model.variant_classes.map((v) => [v, 0]));
    const genes = new Set();
    kept.forEach((r) => {
      subCounts[substitutionClass(r)]++;
      vcCounts[knownVC.has(r.Variant_Classification) ? r.Variant_Classification : 'other_vc']++;
      if (r.Hugo_Symbol) genes.add(r.Hugo_Symbol);
    });
    const x = [Math.log1p(n)]
      .concat(model.subs.map((s) => subCounts[s] / n))
      .concat(model.variant_classes.map((v) => vcCounts[v] / n))
      .concat(model.genes.map((g) => (genes.has(g) ? 1 : 0)))
      .map(Math.fround);
    if (x.length !== model.feature_names.length) throw new Error('Feature vector length does not match the model.');
    const modelGenes = model.genes.filter((g) => genes.has(g));
    return {x, info: {rowsSubmitted: rows.length, mutationsUsed: n, duplicatesRemoved: rows.length - n,
      genesObserved: genes.size, modelGenesFound: modelGenes, subCounts, vcCounts}};
  }

  // Average the leaf class-fractions of every tree (= sklearn RandomForestClassifier.predict_proba).
  function forestScores(model, x) {
    const total = new Array(model.classes.length).fill(0);
    model.trees.forEach((t) => {
      let node = 0;
      while (t.left[node] !== -1) node = x[t.feature[node]] <= t.threshold[node] ? t.left[node] : t.right[node];
      t.value[node].forEach((v, k) => { total[k] += v; });
    });
    return total.map((v) => v / model.trees.length);
  }

  function predict(model, rows) {
    const {x, info} = buildFeatures(model, rows);
    const s = forestScores(model, x);
    const scores = model.classes.map((c, i) => ({cancer: c, studyCode: model.class_study_codes[c], score: s[i]}))
      .sort((a, b) => b.score - a.score);
    return {label: scores[0].cancer, scores, features: x, info, modelVersion: model.model_version};
  }

  return {parseTSV, groupByPatient, buildFeatures, forestScores, predict, substitutionClass};
});
