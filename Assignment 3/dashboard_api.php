<?php

define('JSON_RESPONSE', true);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['error' => 'Only GET requests are supported.']);
    exit;
}

require __DIR__ . '/db.php';

try {
    $counts = $pdo->query(
        'SELECT
            (SELECT COUNT(*) FROM Animal) AS animals,
            (SELECT COUNT(*) FROM Customer) AS customers,
            (SELECT COUNT(*) FROM Sale) AS sales'
    )->fetch();

    $categories = $pdo->query(
        "SELECT
            COALESCE(NULLIF(TRIM(Category), ''), 'Unspecified') AS category,
            COUNT(*) AS animal_count
         FROM Animal
         GROUP BY COALESCE(NULLIF(TRIM(Category), ''), 'Unspecified')
         ORDER BY animal_count DESC, category ASC"
    )->fetchAll();

    $lowStock = $pdo->query(
        'SELECT ItemID, Description, QuantityOnHand
         FROM Merchandise
         ORDER BY QuantityOnHand ASC, ItemID ASC
         LIMIT 5'
    )->fetchAll();

    foreach ($categories as &$category) {
        $category['animal_count'] = (int) $category['animal_count'];
    }
    unset($category);

    foreach ($lowStock as &$item) {
        $item['ItemID'] = (int) $item['ItemID'];
        $item['QuantityOnHand'] = (int) $item['QuantityOnHand'];
    }
    unset($item);

    echo json_encode([
        'totals' => [
            'animals' => (int) $counts['animals'],
            'customers' => (int) $counts['customers'],
            'sales' => (int) $counts['sales'],
        ],
        'categories' => $categories,
        'low_stock' => $lowStock,
        'updated_at' => gmdate('Y-m-d\TH:i:s\Z'),
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

} catch (PDOException $error) {
    error_log($error->getMessage());

    http_response_code(500);

    echo json_encode([
        'error' => 'Dashboard information could not be loaded.',
    ]);
}