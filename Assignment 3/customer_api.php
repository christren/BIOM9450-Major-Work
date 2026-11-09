<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function sendJson(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    sendJson(['error' => 'Only GET requests are supported.'], 405);
}

require __DIR__ . '/db.php';

try {
    // Details mode: retrieve exactly one customer by their unique ID.
    if (isset($_GET['id'])) {
        $rawId = $_GET['id'];

        if (!is_string($rawId)) {
            sendJson(['error' => 'Invalid customer ID.'], 400);
        }

        $id = filter_var(
            $rawId,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false) {
            sendJson(['error' => 'Invalid customer ID.'], 400);
        }

        $statement = $pdo->prepare(
            'SELECT CustomerID, FirstName, LastName,
                    Phone, Address, ZipCode, CityID
             FROM Customer
             WHERE CustomerID = :id'
        );

        $statement->execute(['id' => $id]);
        $customer = $statement->fetch();

        if (!$customer) {
            sendJson(['error' => 'Customer not found.'], 404);
        }

        sendJson(['customer' => $customer]);
    }

    // Search mode: find surnames beginning with the supplied text.
    $query = $_GET['q'] ?? '';

    if (!is_string($query) || strlen($query) > 255) {
        sendJson(['error' => 'Enter a shorter family name.'], 400);
    }

    $query = trim($query);

    if ($query === '') {
        sendJson(['customers' => []]);
    }

    $prefix = strtr($query, [
        '!' => '!!',
        '%' => '!%',
        '_' => '!_',
    ]) . '%';

    $statement = $pdo->prepare(
        "SELECT CustomerID, FirstName, LastName
         FROM Customer
         WHERE LastName LIKE :prefix ESCAPE '!'
         ORDER BY LastName, FirstName, CustomerID
         LIMIT 10"
    );

    $statement->execute(['prefix' => $prefix]);

    sendJson(['customers' => $statement->fetchAll()]);

} catch (PDOException $error) {
    error_log($error->getMessage());

    sendJson(
        ['error' => 'Customer information could not be loaded.'],
        500
    );
}