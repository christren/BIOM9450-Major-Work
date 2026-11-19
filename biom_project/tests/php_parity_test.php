<?php
// Python vs PHP parity test for app/model_predictor.php.
// Run from the repo root:  php biom_project/tests/php_parity_test.php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('CLI only');
$repo = dirname(__DIR__, 2);
require $repo . '/OncoAtlas_BIOM9450_Website/app/model_predictor.php';
$fixtures = json_decode(file_get_contents($repo . '/biom_project/model_output_tcga_v2/parity_fixtures.json'), true, 512, JSON_THROW_ON_ERROR);
$model = model_load();
$failures = 0;

function rows_from_tsv(string $tsv): array {
    $lines = array_values(array_filter(explode("\n", str_replace("\r", '', $tsv)), fn($l) => trim($l) !== ''));
    $header = explode("\t", array_shift($lines));
    return array_map(fn($l) => array_combine($header, array_map('trim', explode("\t", $l))), $lines);
}

if ($model['model_version'] !== $fixtures['model_version']) { echo "FAIL model version mismatch\n"; $failures++; }
foreach ($fixtures['cases'] as $c) {
    $errors = [];
    $p = model_predict(rows_from_tsv($c['tsv']));
    foreach ($p['x'] as $i => $v)
        if (abs($v - $c['expected_features'][$i]) > $fixtures['feature_tolerance']) $errors[] = "feature {$model['feature_names'][$i]}: PHP $v vs Python {$c['expected_features'][$i]}";
    foreach ($p['scores'] as $cls => $s)
        if (abs($s - $c['expected_scores'][$cls]) > $fixtures['score_tolerance']) $errors[] = "$cls: PHP $s vs Python {$c['expected_scores'][$cls]}";
    if ($p['label'] !== $c['expected_label']) $errors[] = "label {$p['label']} vs {$c['expected_label']}";
    if (abs(array_sum($p['scores']) - 1) > 1e-6) $errors[] = 'scores do not sum to 1';
    echo ($errors ? 'FAIL ' : 'PASS ') . $c['name'] . ($errors ? ' - ' . implode('; ', $errors) : '') . "\n";
    $failures += $errors ? 1 : 0;
}
try { model_predict([]); echo "FAIL empty profile was accepted\n"; $failures++; }
catch (RuntimeException $e) { echo "PASS empty profile rejected\n"; }

echo $failures ? "\n$failures test(s) FAILED\n" : "\nAll tests passed\n";
exit($failures ? 1 : 0);
