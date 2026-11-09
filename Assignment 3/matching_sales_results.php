<?php

require __DIR__ . '/db.php';

function reportEscape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$rows = [];
$errorMessage = '';

try {
    $statement = $pdo->query(
        "SELECT DISTINCT
            c.FirstName,
            c.LastName,
            s.SaleID
         FROM Sale AS s
         INNER JOIN Customer AS c
            ON c.CustomerID = s.CustomerID
         INNER JOIN SaleAnimal AS sa
            ON sa.SaleID = s.SaleID
         INNER JOIN Animal AS a
            ON a.AnimalID = sa.AnimalID
         INNER JOIN SaleItem AS si
            ON si.SaleID = s.SaleID
         INNER JOIN Merchandise AS m
            ON m.ItemID = si.ItemID
         WHERE a.Category = m.Category
           AND TRIM(a.Category) <> ''
         ORDER BY c.LastName ASC, c.FirstName ASC, s.SaleID ASC"
    );

    $rows = $statement->fetchAll();

} catch (PDOException $error) {
    error_log($error->getMessage());
    http_response_code(500);
    $errorMessage = 'The sales report could not be loaded. Please try again later.';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category-matching sales report</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="report-body">
    <main class="report-main">
        <h1>Matching purchases</h1>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice error" role="alert">
                <?= reportEscape($errorMessage) ?>
            </div>

        <?php elseif (!$rows): ?>
            <p>No category-matching sales were found.</p>

        <?php else: ?>
            <p>
                <?= count($rows) ?>
                matching <?= count($rows) === 1 ? 'sale' : 'sales' ?>.
                Each sale is listed once.
            </p>

            <div class="table-wrapper" tabindex="0"
                 role="region" aria-label="Category-matching sales">
                <table>
                    <caption>
                        Customers with animal and merchandise categories
                        matching within the same sale
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">First name</th>
                            <th scope="col">Last name</th>
                            <th scope="col">Sale ID</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= reportEscape(
                                    $row['FirstName'] ?? 'Not recorded'
                                ) ?></td>

                                <td><?= reportEscape(
                                    $row['LastName'] ?? 'Not recorded'
                                ) ?></td>

                                <td><?= (int) $row['SaleID'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>