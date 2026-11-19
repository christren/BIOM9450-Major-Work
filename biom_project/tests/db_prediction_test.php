<?php
// Database tests for the predictive-analysis section (needs the cancer_portal MySQL database).
// Run from the repo root:  php biom_project/tests/db_prediction_test.php [export.json]
// The optional export file lets predict_parity.test.js compare JS with PHP on every database patient.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('CLI only');
$site = dirname(__DIR__, 2) . '/OncoAtlas_BIOM9450_Website';
require $site . '/app/bootstrap.php';
require $site . '/app/model_predictor.php';
$pdo = db();
$model = model_load();
$train = array_flip($model['train_patients']);
$failures = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $failures;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . ($detail !== '' ? " - $detail" : '') . "\n";
    if (!$ok) $failures++;
}
function counts(PDO $pdo): array {
    return array_map(fn($t) => (int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn(), ['Prediction' => 'Prediction', 'PredictionScore' => 'PredictionScore', 'PredictionMutation' => 'PredictionMutation']);
}

// 1. Every model class maps to exactly one CancerType row.
$codes = $pdo->query('SELECT StudyCode, CancerName FROM CancerType')->fetchAll(PDO::FETCH_KEY_PAIR);
$mapOk = count($codes) === count($model['classes']);
foreach ($model['classes'] as $cls) $mapOk = $mapOk && ($codes[$model['class_study_codes'][$cls]] ?? null) === $cls;
check('model classes map to CancerType (StudyCode and CancerName)', $mapOk);

// 2. Predict every patient in the database from ModelInput.
$byPatient = [];
foreach ($pdo->query('SELECT * FROM ModelInput ORDER BY SampleMutationID') as $r) $byPatient[(int)$r['PatientID']][] = $r;
$truth = $pdo->query('SELECT p.PatientID, p.TCGAPatientID, c.CancerName FROM Patient p JOIN CancerType c ON c.CancerTypeID=p.CancerTypeID')->fetchAll(PDO::FETCH_UNIQUE);
$export = []; $heldOut = 0; $heldOutCorrect = 0; $sumOk = true;
foreach ($byPatient as $pid => $rows) {
    $p = model_predict($rows);
    $sumOk = $sumOk && abs(array_sum($p['scores']) - 1) < 1e-6;
    $tcga = $truth[$pid]['TCGAPatientID'] ?? null;
    if ($tcga !== null && !isset($train[$tcga])) { $heldOut++; $heldOutCorrect += $p['label'] === $truth[$pid]['CancerName'] ? 1 : 0; }
    $export[] = ['patient_id' => $pid, 'rows' => array_map(fn($r) => array_map(fn($v) => $v === null ? '' : (string)$v, $r), $rows), 'scores' => $p['scores']];
}
check('predicted every database patient with a mutation profile', count($byPatient) >= 300, count($byPatient) . ' patients');
check('all score sets sum to 1', $sumOk);
echo sprintf("INFO held-out test patients in the demo database: %d, correctly predicted: %d (%.1f%%)\n", $heldOut, $heldOutCorrect, $heldOut ? 100 * $heldOutCorrect / $heldOut : 0);
if (isset($argv[1])) { file_put_contents($argv[1], json_encode($export)); echo "INFO exported database profiles to {$argv[1]}\n"; }

// 3. A database error part-way through saving rolls back everything.
$pid = array_key_first($byPatient);
$ids = array_map(fn($r) => (int)$r['SampleMutationID'], $byPatient[$pid]);
$user = (int)$pdo->query("SELECT UserID FROM AppUser WHERE Role='Oncologist' LIMIT 1")->fetchColumn();
$before = counts($pdo);
$pdo->exec("CREATE TRIGGER test_fail_prediction_mutation BEFORE INSERT ON PredictionMutation FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='simulated failure'");
try {
    try { prediction_save($pdo, $pid, $user, model_predict($byPatient[$pid]), $ids); $threw = false; }
    catch (PDOException $e) { $threw = str_contains($e->getMessage(), 'simulated failure'); }
} finally { $pdo->exec('DROP TRIGGER IF EXISTS test_fail_prediction_mutation'); }
check('simulated write failure raises an error', $threw);
check('rollback: no Prediction/PredictionScore/PredictionMutation rows left behind', counts($pdo) === $before, json_encode(counts($pdo)));

// 4. Mutations belonging to another patient are refused.
$other = array_keys($byPatient)[1];
try { prediction_save($pdo, $pid, $user, model_predict($byPatient[$pid]), array_merge($ids, [(int)$byPatient[$other][0]['SampleMutationID']])); $refused = false; }
catch (RuntimeException $e) { $refused = str_contains($e->getMessage(), 'do not belong'); }
check("refuses to link another patient's mutations", $refused);
check('nothing saved after refusal', counts($pdo) === $before);

echo $failures ? "\n$failures test(s) FAILED\n" : "\nAll tests passed\n";
exit($failures ? 1 : 0);
