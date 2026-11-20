<?php
// AJAX endpoint for the Patients page.
// 1. Read the GET search filters.
// 2. Make a prepared SQL query.
// 3. Return JSON for JavaScript to display.
require_once __DIR__ . '/../app/bootstrap.php';
require_professional();
require_get();

try {
    // Search text and optional filters from patients.php.
    $search = trim((string)($_GET['q'] ?? ''));
    $cancer = trim((string)($_GET['cancer'] ?? ''));
    $sex = trim((string)($_GET['sex'] ?? ''));
    $page = int_param($_GET['page'] ?? 1, 1, 10000) ?? 1;
    $perPage = 20;

    if (strlen($search) > 100) {
        json_response(['error' => 'Search is too long'], 422);
    }

    // Start with all patients. Add WHERE conditions only when needed.
    // ? placeholders keep user input separate from the SQL text.
    $conditions = [];
    $values = [];

    if ($search !== '') {
        $conditions[] = '(LOWER(p.Name) LIKE ? OR LOWER(p.TCGAPatientID) LIKE ?)';
        $text = '%' . strtolower($search) . '%';
        $values[] = $text;
        $values[] = $text;
    }

    if ($cancer !== '') {
        $cancerId = int_param($cancer, 1, 1000);
        if (!$cancerId) {
            json_response(['error' => 'Invalid cancer type'], 422);
        }
        $conditions[] = 'p.CancerTypeID = ?';
        $values[] = $cancerId;
    }

    if ($sex !== '') {
        if ($sex !== 'Male' && $sex !== 'Female') {
            json_response(['error' => 'Invalid sex filter'], 422);
        }
        $conditions[] = 'p.Sex = ?';
        $values[] = $sex;
    }

    // Join the separate conditions with AND.
    $where = '';
    if (count($conditions) > 0) {
        $where = ' WHERE ' . implode(' AND ', $conditions);
    }

    // The COUNT query tells JavaScript how many result pages exist.
    $total = (int)query_value('SELECT COUNT(*) FROM Patient p' . $where, $values);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;

    // These two smaller subqueries count samples and mutations per patient.
    // LEFT JOIN includes patients without a recorded cancer cohort.
    $sql = 'SELECT
                p.PatientID, p.TCGAPatientID, p.Name, p.Age, p.Sex,
                p.DemographicsSynthetic, c.StudyCode, c.DiagnosisLabel,
                (SELECT COUNT(*) FROM TumorSample s
                 WHERE s.PatientID = p.PatientID) AS Samples,
                (SELECT COUNT(*) FROM SampleMutation sm
                 INNER JOIN TumorSample s2 ON s2.SampleID = sm.SampleID
                 WHERE s2.PatientID = p.PatientID) AS Mutations
            FROM Patient p
            LEFT JOIN CancerType c ON c.CancerTypeID = p.CancerTypeID'
            . $where .
            ' ORDER BY p.PatientID DESC LIMIT ? OFFSET ?';

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
    error_log('Patients API: ' . $error->getMessage());
    json_response(['error' => 'Could not load patient records'], 500);
}
