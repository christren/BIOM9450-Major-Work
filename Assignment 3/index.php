<?php

require __DIR__ . '/db.php';

$statement = $pdo->query('SELECT COUNT(*) FROM Animal');
$animalCount = (int) $statement->fetchColumn();

require __DIR__ . '/includes/header.php';

?>

<section class="card">
    <h2>Welcome to PetStore</h2>

    <p>Manage animals, customers, merchandise and sales.</p>

    <p>Database connection: working</p>
    <p>Animals in the database: <?= $animalCount ?></p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>