// Python vs JavaScript parity test for the web model.
// Run from the repo root:  node biom_project/tests/predict_parity.test.js
'use strict';
const fs = require('fs');
const path = require('path');
const assert = require('assert');

const repo = path.resolve(__dirname, '..', '..');
const OncoPredict = require(path.join(repo, 'OncoAtlas_BIOM9450_Website', 'assets', 'js', 'predict.js'));
const model = JSON.parse(fs.readFileSync(path.join(repo, 'OncoAtlas_BIOM9450_Website', 'assets', 'model', 'rf_model.json'), 'utf8'));
const fixtures = JSON.parse(fs.readFileSync(path.join(repo, 'biom_project', 'model_output_tcga_v2', 'parity_fixtures.json'), 'utf8'));

let failures = 0;
function check(name, fn) {
  try { fn(); console.log('PASS', name); } catch (e) { failures++; console.log('FAIL', name, '-', e.message); }
}

check('website model version matches fixtures', () => assert.strictEqual(model.model_version, fixtures.model_version));

fixtures.cases.forEach((c) => {
  check(`${c.name}: features, scores and label match Python`, () => {
    const rows = OncoPredict.parseTSV(c.tsv, model.required_columns);
    const patients = OncoPredict.groupByPatient(rows);
    assert.strictEqual(patients.size, 1, 'expected one patient per fixture');
    const result = OncoPredict.predict(model, [...patients.values()][0]);
    assert.strictEqual(result.features.length, c.expected_features.length);
    result.features.forEach((v, i) => {
      if (Math.abs(v - c.expected_features[i]) > fixtures.feature_tolerance)
        throw new Error(`feature ${model.feature_names[i]}: JS ${v} vs Python ${c.expected_features[i]}`);
    });
    result.scores.forEach((s) => {
      const diff = Math.abs(s.score - c.expected_scores[s.cancer]);
      if (diff > fixtures.score_tolerance) throw new Error(`${s.cancer}: JS ${s.score} vs Python ${c.expected_scores[s.cancer]}`);
    });
    assert.strictEqual(result.label, c.expected_label);
    const sum = result.scores.reduce((a, s) => a + s.score, 0);
    assert.ok(Math.abs(sum - 1) < 1e-6, 'scores should sum to 1, got ' + sum);
  });
});

const header = model.required_columns.join('\t');
const good = fixtures.cases.find((c) => c.name === 'edge_single_mutation').tsv;
check('rejects an empty file', () => assert.throws(() => OncoPredict.parseTSV('', model.required_columns), /empty/));
check('rejects header-only file', () => assert.throws(() => OncoPredict.parseTSV(header + '\n', model.required_columns), /no mutation rows/));
check('rejects missing columns', () => assert.throws(() => OncoPredict.parseTSV('Hugo_Symbol\tChromosome\nTP53\t17\n', model.required_columns), /Required columns missing: Start_Position/));
check('rejects comma-separated file', () => assert.throws(() => OncoPredict.parseTSV(good.replace(/\t/g, ','), model.required_columns), /tab-delimited/));
check('rejects ragged rows', () => assert.throws(() => OncoPredict.parseTSV(good.replace(/\n+$/, '') + '\textra\n', model.required_columns), /columns/));
check('detects multiple patients in one file', () => {
  const two = fixtures.cases.slice(0, 2).map((c) => c.tsv.replace(/\n+$/, '').split('\n')).reduce((a, b) => a.concat(b.slice(1)));
  const groups = OncoPredict.groupByPatient(OncoPredict.parseTSV(two.join('\n'), model.required_columns));
  assert.strictEqual(groups.size, 2);
});
check('strand normalisation (G>A == C>T)', () => {
  assert.strictEqual(OncoPredict.substitutionClass({Variant_Type: 'SNP', Reference_Allele: 'G', Tumor_Seq_Allele2: 'A'}), 'C>T');
  assert.strictEqual(OncoPredict.substitutionClass({Variant_Type: 'SNP', Reference_Allele: 'T', Tumor_Seq_Allele2: 'G'}), 'T>G');
  assert.strictEqual(OncoPredict.substitutionClass({Variant_Type: 'DEL', Reference_Allele: 'CT', Tumor_Seq_Allele2: '-'}), 'indel');
});

// Optional: node predict_parity.test.js <export.json> (made by db_prediction_test.php) compares JS with PHP on every DB patient.
if (process.argv[2]) {
  check('JS matches PHP on every database patient', () => {
    const profiles = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
    let worst = 0;
    profiles.forEach((p) => {
      const r = OncoPredict.predict(model, p.rows);
      r.scores.forEach((s) => { worst = Math.max(worst, Math.abs(s.score - p.scores[s.cancer])); });
    });
    console.log(`  ${profiles.length} patients, largest score difference ${worst.toExponential(2)}`);
    assert.ok(worst < 1e-9, 'JS and PHP disagree');
  });
}

console.log(failures ? `\n${failures} test(s) FAILED` : '\nAll tests passed');
process.exit(failures ? 1 : 0);
