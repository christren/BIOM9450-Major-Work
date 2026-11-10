<?php

$actions = [
    [
        'group' => 'Animals',
        'title' => 'Search and edit',
        'description' => 'Find animals, compare prices and update their records.',
        'link' => 'animal_search.php',
    ],
    [
        'group' => 'Animals',
        'title' => 'Add an animal',
        'description' => 'Register a new animal with an optional photo.',
        'link' => 'animal_add.php',
    ],
    [
        'group' => 'Customers',
        'title' => 'Find a customer',
        'description' => 'Look up customers using live surname suggestions.',
        'link' => 'customer_lookup.php',
    ],
    [
        'group' => 'Customers',
        'title' => 'Register a customer',
        'description' => 'Create a customer record before placing an order.',
        'link' => 'customer_register.php',
    ],
    [
        'group' => 'Sales',
        'title' => 'Merchandise checkout',
        'description' => 'Build a multi-item order with stock checks and a receipt.',
        'link' => 'checkout.php',
    ],
    [
        'group' => 'Insights',
        'title' => 'View dashboard',
        'description' => 'Explore animal categories, store totals and low stock.',
        'link' => 'dashboard.php',
    ],
];

require __DIR__ . '/includes/header.php';

?>

<?php
$hasHeroImage = is_file(
    __DIR__ . '/images/site/petstore-hero.jpg'
);
?>

<section class="card home-intro <?= $hasHeroImage ? 'has-hero-photo' : '' ?>">
    <div class="hero-copy">
        <p class="eyebrow">PetStore workspace</p>

        <h1>A little care.<br>A better-run store.</h1>

        <p class="home-description">
            Keep animal records organised, find your customers
            and manage merchandise orders- all in one place.
        </p>

        <div class="form-actions">
            <a class="button-primary" href="checkout.php">
                Start an order
            </a>

            <a class="button-secondary" href="dashboard.php">
                View dashboard
            </a>
        </div>

        <p class="hero-note">
            Animals, customers and insights. Thoughtfully connected.
        </p>
    </div>

    <?php if ($hasHeroImage): ?>
        <figure class="hero-media">
            <img
                src="images/site/petstore-hero.jpg"
                alt=""
                width="1200"
                height="1000"
                decoding="async">
        </figure>
    <?php endif; ?>
</section>

<section class="home-actions" aria-labelledby="quick-actions-title">
    <h2 id="quick-actions-title">Quick actions</h2>

    <div class="action-grid">
        <?php foreach ($actions as $action): ?>
            <a class="card action-card"
               href="<?= htmlspecialchars(
                   $action['link'], ENT_QUOTES, 'UTF-8'
               ) ?>">

                <span class="action-group">
                    <?= htmlspecialchars(
                        $action['group'], ENT_QUOTES, 'UTF-8'
                    ) ?>
                </span>

                <h3><?= htmlspecialchars(
                    $action['title'], ENT_QUOTES, 'UTF-8'
                ) ?></h3>

                <p><?= htmlspecialchars(
                    $action['description'], ENT_QUOTES, 'UTF-8'
                ) ?></p>

                <span class="action-open" aria-hidden="true">Open →</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="card results-section">
    <h2>Explore matching purchases</h2>

    <p>
        Find sales containing an animal and merchandise from the same category.
    </p>

    <a class="button-secondary" href="matching_sales.php">
        Open matching-sales report
    </a>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>