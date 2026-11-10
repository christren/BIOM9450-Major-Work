<?php require __DIR__ . '/includes/header.php'; ?>

<section class="card">
    <div class="results-toolbar">
        <div>
            <h2>Store dashboard</h2>
            <p>Live summaries from the PetStore database.</p>
        </div>

        <button id="dashboard-refresh" type="button">
            Refresh data
        </button>
    </div>

    <p id="dashboard-status" role="status" aria-live="polite">
        Loading dashboard...
    </p>

    <small>
        Refreshes automatically every 60 seconds while this tab is visible.
    </small>

    <noscript>
        <p>Enable JavaScript to load the dashboard.</p>
    </noscript>
</section>

<section class="dashboard-metrics" aria-label="Store totals">
    <article class="card metric-card">
        <h2>Total animals</h2>
        <p id="total-animals" class="metric-value">—</p>
    </article>

    <article class="card metric-card">
        <h2>Total customers</h2>
        <p id="total-customers" class="metric-value">—</p>
    </article>

    <article class="card metric-card">
        <h2>Total sales</h2>
        <p id="total-sales" class="metric-value">—</p>
    </article>
</section>

<div class="dashboard-panels">
    <section class="card">
        <h2>Animals by category</h2>
        <p>Each bar shows the number of animals in that category.</p>

        <ul id="category-chart" class="category-chart"
            aria-label="Animal counts by category"></ul>
    </section>

    <section class="card">
        <h2>Lowest-stock merchandise</h2>

        <div class="table-wrapper" tabindex="0"
             role="region" aria-label="Lowest-stock merchandise">
            <table>
                <caption>Five lowest-stock products, including zero stock</caption>

                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Product</th>
                        <th scope="col">Stock</th>
                    </tr>
                </thead>

                <tbody id="low-stock-body">
                    <tr>
                        <td colspan="3">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="js/dashboard.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>