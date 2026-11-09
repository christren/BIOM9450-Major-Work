<?php require __DIR__ . '/includes/header.php'; ?>

<section class="card">
    <h2>Category-matching sales</h2>

    <p>
        Customers who purchased an animal and merchandise from the
        same category within a single sale.
    </p>

    <p>
        E.g. A dog and dog merchandise must appear in the
        same transaction to qualify.
    </p>

    <iframe
        class="report-frame"
        src="matching_sales_results.php"
        title="Customers with category-matching animal and merchandise purchases">
    </iframe>

    <p class="report-help">
        <a href="matching_sales_results.php"
           target="_blank" rel="noopener">
            Open the report in a separate tab
        </a>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>