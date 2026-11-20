<?php
// Gene search AJAX endpoint
// The browser sends GET parameters; this file returns JSON.
require_once __DIR__ . '/../app/bootstrap.php';
require_professional();
require_get();

try {
    $search = trim((string)($_GET['q'] ?? ''));
    $cancer = trim((string)($_GET['cancer'] ?? ''));
    $page = int_param($_GET['page'] ?? 1, 1, 10000) ?? 1;
    $perPage = 20;

    if (strlen($search) > 100) {
        json_response(['error' => 'Search too long'], 422);
    }

    // We use an optional cohort filter, without changing the gene table.
    $cancerId = null;
    if ($cancer !== '') {
        $cancerId = int_param($cancer, 1, 1000);
        if (!$cancerId) json_response(['error' => 'Invalid cancer cohort'], 422);
    }

    // LIKE allows partial symbol searches, e.g. "TP" matching "TP53".
    $where = '';
    $values = [];
    if ($search !== '') {
        $where = ' WHERE LOWER(g.GeneSymbol) LIKE ?';
        $values[] = '%' . strtolower($search) . '%';
    }

    // When no cohort is selected, include every gene, even one with no
    // recorded mutation. With a cohort, include only genes observed there.
    if ($cancerId === null) {
        $total = (int)query_value(
            'SELECT COUNT(*) FROM Gene g' . $where, $values
        );
    } else {
        $extra = $where === '' ? ' WHERE ' : ' AND ';
        $countSql = 'SELECT COUNT(DISTINCT g.GeneID)
                     FROM Gene g
                     INNER JOIN SampleMutation sm ON sm.GeneID = g.GeneID
                     INNER JOIN TumorSample s ON s.SampleID = sm.SampleID
                     INNER JOIN Patient p ON p.PatientID = s.PatientID'
                     . $where . $extra . 'p.CancerTypeID = ?';
        $total = (int)query_value($countSql, array_merge($values, [$cancerId]));
    }

    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    // LEFT JOIN keeps gene records with no linked mutation if no
    // cohort filter is applied.
    $joins = ' FROM Gene g
               LEFT JOIN SampleMutation sm ON sm.GeneID = g.GeneID
               LEFT JOIN TumorSample s ON s.SampleID = sm.SampleID
               LEFT JOIN Patient p ON p.PatientID = s.PatientID';

    $params = $values;
    if ($cancerId === null) {
        $mutationCount = 'COUNT(sm.SampleMutationID)';
        $sampleCount = 'COUNT(DISTINCT s.SampleID)';
        $patientCount = 'COUNT(DISTINCT p.PatientID)';
        $having = '';
    } else {
        // Count only mutations in the selected cancer type.
        // This avoids changing the underlying patient or mutation records.
        $condition = 'p.CancerTypeID = ' . (int)$cancerId;
        $mutationCount = 'COUNT(CASE WHEN ' . $condition . ' THEN sm.SampleMutationID END)';
        $sampleCount = 'COUNT(DISTINCT CASE WHEN ' . $condition . ' THEN s.SampleID END)';
        $patientCount = 'COUNT(DISTINCT CASE WHEN ' . $condition . ' THEN p.PatientID END)';
        $having = ' HAVING MutationCount > 0';
    }

    // All columns/aliases below are already used by assets/js/app.js.
    $sql = 'SELECT g.GeneID, g.GeneSymbol, '
           . $mutationCount . ' AS MutationCount, '
           . $sampleCount . ' AS SampleCount, '
           . $patientCount . ' AS PatientCount'
           . $joins . $where
           . ' GROUP BY g.GeneID, g.GeneSymbol'
           . $having
           . ' ORDER BY MutationCount DESC, g.GeneSymbol LIMIT ? OFFSET ?';

    // Execute the prepared SELECT and bind all parameters through MySQLi.
    // Keep LIMIT/OFFSET as integers for pagination.
    $allValues = array_merge($params, [$perPage, $offset]);
    $stmt = sql_run($sql, $allValues);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    json_response([
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'pages' => $pages
    ]);
} catch (Throwable $error) {
    error_log('Genes API: ' . $error->getMessage());
    json_response(['error' => 'Could not load genes'], 500);
}
