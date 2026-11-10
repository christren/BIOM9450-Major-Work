<?php

$currentPage = basename($_SERVER['SCRIPT_NAME']);

$navigation = [
    'index.php' => 'Home',
    'animal_add.php' => 'Add animal',
    'animal_search.php' => 'Search animals',
    'matching_sales.php' => 'Matching sales',
    'customer_lookup.php' => 'Find customer',
    'customer_register.php' => 'Register customer',
    'checkout.php' => 'Checkout',
    'dashboard.php' => 'Dashboard',
];

$activePage = match ($currentPage) {
    'animal_edit.php' => 'animal_search.php',
    'checkout_receipt.php' => 'checkout.php',
    default => $currentPage,
};

$pageTitle = match ($currentPage) {
    'animal_edit.php' => 'Edit animal',
    'checkout_receipt.php' => 'Order receipt',
    default => $navigation[$currentPage] ?? 'PetStore',
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars(
        $pageTitle . ' | PetStore Management',
        ENT_QUOTES,
        'UTF-8'
    ) ?></title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <header class="site-header">
        <div class="header-inner">
            <div class="brand-row">
                <a class="brand" href="index.php">PetStore Management</a>
                <span class="environment-label">Local assignment learning application</span>
            </div>

            <nav aria-label="Main navigation">
                <?php foreach ($navigation as $file => $label): ?>
                    <a href="<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>"
                       <?= $file === $activePage
                           ? 'aria-current="page"' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

    <main id="main-content" tabindex="-1">