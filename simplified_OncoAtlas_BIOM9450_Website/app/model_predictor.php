<?php
declare(strict_types=1);
/*
Server-side model predictor; reads assets/model/rf_model.json.
Component Owner: Chris (z5417031)
Summary: PHP copy of the exported random forest plus the transactional save, ensuring parity with the Python and JavaScript implementations.

Tested by biom_project/tests/php_parity_test.php and db_prediction_test.php.

*/
const MODEL_JSON_PATH = __DIR__ . '/../assets/model/rf_model.json';

function model_load(): array {
    static $model;
    if ($model === null) {
        $text = @file_get_contents(MODEL_JSON_PATH);
        if ($text === false) throw new RuntimeException('Prediction model file is missing (assets/model/rf_model.json).');
        $model = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    }
    return $model;
}

// Round to 32-bit float, because scikit-learn compares float32 feature values against the tree thresholds.
function model_f32(float $v): float { return unpack('g', pack('g', $v))[1]; }

function model_substitution(array $r): string {
    $comp = ['A' => 'T', 'T' => 'A', 'C' => 'G', 'G' => 'C'];
    $ref = (string)($r['Reference_Allele'] ?? ''); $alt = (string)($r['Tumor_Seq_Allele2'] ?? ''); $type = (string)($r['Variant_Type'] ?? '');
    if ($type === 'SNP' && isset($comp[$ref], $comp[$alt])) {
        $flip = $ref === 'A' || $ref === 'G';                 // express every SNP on the C/T strand
        return ($flip ? $comp[$ref] : $ref) . '>' . ($flip ? $comp[$alt] : $alt);
    }
    return ($type === 'INS' || $type === 'DEL') ? 'indel' : 'other';
}

/** Rows for ONE patient -> ['x' => feature vector, 'used' => rows kept after de-duplication]. */
function model_features(array $model, array $rows): array {
    $seen = []; $kept = [];
    foreach ($rows as $r) {                                   // de-duplicate by genomic identity, keep first
        $key = implode("\x01", array_map(fn($c) => (string)($r[$c] ?? ''), $model['dedup_key']));
        if (!isset($seen[$key])) { $seen[$key] = true; $kept[] = $r; }
    }
    $n = count($kept);
    if ($n === 0) throw new RuntimeException('No mutations to analyse.');
    $known = array_flip(array_slice($model['variant_classes'], 0, -1));
    $subs = array_fill_keys($model['subs'], 0); $vcs = array_fill_keys($model['variant_classes'], 0); $genes = [];
    foreach ($kept as $r) {
        $subs[model_substitution($r)]++;
        $vc = (string)($r['Variant_Classification'] ?? '');
        $vcs[isset($known[$vc]) ? $vc : 'other_vc']++;
        if (($r['Hugo_Symbol'] ?? '') !== '') $genes[(string)$r['Hugo_Symbol']] = true;
    }
    $x = [log1p($n)];
    foreach ($model['subs'] as $s) $x[] = $subs[$s] / $n;
    foreach ($model['variant_classes'] as $v) $x[] = $vcs[$v] / $n;
    foreach ($model['genes'] as $g) $x[] = isset($genes[$g]) ? 1.0 : 0.0;
    if (count($x) !== count($model['feature_names'])) throw new RuntimeException('Feature vector length does not match the model.');
    return ['x' => array_map('model_f32', $x), 'used' => $kept];
}

/** Average leaf class-fractions over all trees. Returns [class name => score]. */
function model_scores(array $model, array $x): array {
    $total = array_fill(0, count($model['classes']), 0.0);
    foreach ($model['trees'] as $t) {
        $node = 0;
        while ($t['left'][$node] !== -1) $node = $x[$t['feature'][$node]] <= $t['threshold'][$node] ? $t['left'][$node] : $t['right'][$node];
        foreach ($t['value'][$node] as $k => $v) $total[$k] += $v;
    }
    $trees = count($model['trees']);
    return array_combine($model['classes'], array_map(fn($v) => $v / $trees, $total));
}

function model_predict(array $rows): array {
    $model = model_load();
    $f = model_features($model, $rows);
    $scores = model_scores($model, $f['x']);
    arsort($scores);
    return ['model_version' => $model['model_version'], 'scores' => $scores, 'label' => array_key_first($scores),
            'study_codes' => $model['class_study_codes'], 'used' => $f['used'], 'x' => $f['x']];
}

/**
 * Save Prediction + 6 PredictionScore rows + PredictionMutation links in ONE transaction (all or nothing).
 * $sampleMutationIds are the exact ModelInput rows the model received; they must all belong to $patientId.
 */
function prediction_save(PDO $pdo, int $patientId, int $userId, array $prediction, array $sampleMutationIds): int {
    $typeIds = [];
    foreach ($pdo->query('SELECT CancerTypeID, StudyCode FROM CancerType') as $r) $typeIds[$r['StudyCode']] = (int)$r['CancerTypeID'];
    foreach ($prediction['scores'] as $cls => $s) {
        if (!isset($typeIds[$prediction['study_codes'][$cls] ?? ''])) throw new RuntimeException("Model class $cls has no matching CancerType.");
        if (!is_finite($s) || $s < 0 || $s > 1) throw new RuntimeException("Invalid score for $cls.");
    }
    if (count($prediction['scores']) !== count($typeIds) || abs(array_sum($prediction['scores']) - 1) > 1e-6)
        throw new RuntimeException('Scores must cover every cancer type and sum to 1.');
    $ids = array_values(array_unique(array_map('intval', $sampleMutationIds)));
    if (!$ids) throw new RuntimeException('A prediction must be linked to at least one mutation row.');

    $pdo->beginTransaction();
    try {
        $place = implode(',', array_fill(0, count($ids), '?'));
        $own = $pdo->prepare("SELECT COUNT(*) FROM SampleMutation sm JOIN TumorSample s ON s.SampleID=sm.SampleID WHERE s.PatientID=? AND sm.SampleMutationID IN ($place)");
        $own->execute(array_merge([$patientId], $ids));
        if ((int)$own->fetchColumn() !== count($ids)) throw new RuntimeException('Some input mutations do not belong to this patient.');

        $pdo->prepare('INSERT INTO Prediction (PatientID, CreatedBy, ModelVersion) VALUES (?,?,?)')->execute([$patientId, $userId, $prediction['model_version']]);
        $predictionId = (int)$pdo->lastInsertId();
        $score = $pdo->prepare('INSERT INTO PredictionScore (PredictionID, CancerTypeID, Score) VALUES (?,?,?)');
        foreach ($prediction['scores'] as $cls => $s) $score->execute([$predictionId, $typeIds[$prediction['study_codes'][$cls]], number_format($s, 9, '.', '')]);
        foreach (array_chunk($ids, 500) as $chunk) {
            $values = implode(',', array_fill(0, count($chunk), '(?,?)'));
            $params = [];
            foreach ($chunk as $id) array_push($params, $predictionId, $id);
            $pdo->prepare("INSERT INTO PredictionMutation (PredictionID, SampleMutationID) VALUES $values")->execute($params);
        }
        $pdo->commit();
        return $predictionId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
