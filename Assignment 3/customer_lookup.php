<?php require __DIR__ . '/includes/header.php'; ?>

<section class="card">
    <h2>Find a customer</h2>
    <p>Start typing a family name, then select a customer.</p>

    <div class="field">
        <label for="customer-surname">Family name</label>

        <input id="customer-surname" type="search"
               maxlength="255" autocomplete="off"
               aria-describedby="customer-help customer-status">

        <small id="customer-help">
            Suggestions include first names and customer IDs.
            Use Tab to reach suggestions, then Enter to select.
        </small>
    </div>

    <p id="customer-status" role="status" aria-live="polite">
        Enter a family name to begin.
    </p>

    <ul id="customer-suggestions" class="customer-suggestions"
        aria-label="Matching customers"></ul>

    <noscript>
        <p>Note: Enable JavaScript to use live customer lookup.</p>
    </noscript>
</section>

<section id="customer-details-panel" class="card results-section" hidden>
    <h2>Customer details</h2>
    <dl id="customer-details" class="customer-details"></dl>
</section>

<script src="js/customer_lookup.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>