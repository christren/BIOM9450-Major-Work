<?php
// AJAX endpoint for the Mutation explorer.
// The HTML form calls this file through JavaScript fetch().
// We build optional filters, JOIN the related tables, then return JSON.
require_once __DIR__ . '/../app/bootstrap.php';
require_professional();
require_get();

try {
    $page = int_param($_GET['page'] ?? 1, 1, 10000) ?? 1;
    $perPage = 25;

    // Arrays store optional SQL conditions and their prepared values.
    $conditions = [];
    $values = [];

    // Gene name (partial matching, like searching an animal name).
    $gene = trim((string)($_GET['gene'] ?? ''));
    if (strlen($gene) > 100) json_response(['error' => 'Gene filter too long'], 422);
    if ($gene !== '') {
        $conditions[] = 'LOWER(g.GeneSymbol) LIKE ?';
        $values[] = '%' . strtolower($gene) . '%';
    }

    // Cancer cohort, stored in Patient as CancerTypeID.
    $cancer = trim((string)($_GET['cancer'] ?? ''));
    if ($cancer !== '') {
        $cancerId = int_param($cancer, 1, 1000);
        if (!$cancerId) json_response(['error' => 'Invalid cancer filter'], 422);
        $conditions[] = 'p.CancerTypeID = ?';
        $values[] = $cancerId;
    }

    // Genomic chromosome and exact mutation position.
    $chromosome = trim((string)($_GET['chr'] ?? ''));
    if (strlen($chromosome) > 20) json_response(['error' => 'Chromosome filter too long'], 422);
    if ($chromosome !== '') {
        $conditions[] = 'v.Chromosome = ?';
        $values[] = $chromosome;
    }

    $position = trim((string)($_GET['position'] ?? ''));
    if ($position !== '') {
        if (!preg_match('/^[0-9]{1,18}$/', $position) || $position === '0') {
            json_response(['error' => 'Position must be a positive integer'], 422);
        }
        $conditions[] = 'v.MutationPosition = ?';
        $values[] = $position;
    }

    // Optional mutation classification, sample barcode and patient ID.
    $classification = trim((string)($_GET['type'] ?? ''));
    if (strlen($classification) > 100) json_response(['error' => 'Classification too long'], 422);
    if ($classification !== '') {
        $conditions[] = 'sm.MutationType = ?';
        $values[] = $classification;
    }

    $sample = trim((string)($_GET['sample'] ?? ''));
    if (strlen($sample) > 100) json_response(['error' => 'Sample filter too long'], 422);
    if ($sample !== '') {
        $conditions[] = 'LOWER(s.TCGASampleBarcode) LIKE ?';
        $values[] = '%' . strtolower($sample) . '%';
    }

    $patient = trim((string)($_GET['patient'] ?? ''));
    if ($patient !== '') {
        $patientId = int_param($patient);
        if (!$patientId) json_response(['error' => 'Invalid patient ID'], 422);
        $conditions[] = 'p.PatientID = ?';
        $values[] = $patientId;
    }

    // Each JOIN connects a foreign key to the related table's primary key.
    $tables = ' FROM SampleMutation sm
                INNER JOIN TumorSample s ON s.SampleID = sm.SampleID
                INNER JOIN Patient p ON p.PatientID = s.PatientID
                INNER JOIN Gene g ON g.GeneID = sm.GeneID
                INNER JOIN Variant v ON v.VariantID = sm.VariantID
                LEFT JOIN CancerType c ON c.CancerTypeID = p.CancerTypeID';

    $where = '';
    if ($conditions) $where = ' WHERE ' . implode(' AND ', $conditions);

    $total = (int)query_value('SELECT COUNT(*)' . $tables . $where, $values);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    $sql = 'SELECT sm.SampleMutationID, g.GeneSymbol,
                   sm.MutationType, sm.VariantType, sm.Impact, sm.ProteinChange,
                   v.GenomeBuild, v.Chromosome, v.MutationPosition, v.EndPosition,
                   v.ReferenceAllele, v.AlternateAllele, s.TCGASampleBarcode,
                   p.PatientID, p.Name AS PatientName, c.StudyCode'
           . $tables . $where
           . ' ORDER BY sm.SampleMutationID DESC LIMIT ? OFFSET ?';

    // Execute the prepared SELECT and bind all parameters through MySQLi.
    // Keep LIMIT/OFFSET as integers for pagination.
    $allValues = array_merge($values, [$perPage, $offset]);
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
    error_log('Mutation API: ' . $error->getMessage());
    json_response(['error' => 'Could not load mutation records'], 500);
}
