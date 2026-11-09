<?php

require __DIR__ . '/db.php';

$statement = $pdo->query('SELECT COUNT(*) FROM Animal');
$animalCount = (int) $statement->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetStore</title>
</head>
<body>
    <h1>PetStore</h1>

    <p>PHP connected to MySQL successfully!</p>
    <p>Animals in the database: <?= $animalCount ?></p>
</body>
</html>