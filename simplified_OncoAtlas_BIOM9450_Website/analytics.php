<?php
// Researcher-only analytics page.
// Oncologists can search patient mutations, but only researchers can
// compare mutation counts across a SELECTED LIST of genes.
// The charts receive real MySQL results through api/stats.php (AJAX).
require_once __DIR__ . '/app/layout.php';
require_researcher();
audit('VIEW_ANALYTICS', 'SampleMutation', null);

// Present common genes as checkboxes. Their names and observation counts
// come from the database, not hardcoded JavaScript or imaginary example data.
$geneChoices = query_rows(
    'SELECT g.GeneSymbol, COUNT(sm.SampleMutationID) AS Mutations
     FROM Gene g
     INNER JOIN SampleMutation sm ON sm.GeneID = g.GeneID
     GROUP BY g.GeneID, g.GeneSymbol
     ORDER BY Mutations DESC, g.GeneSymbol
     LIMIT 16'
);

start_page(
    'Genomic analytics',
    'analytics.php',
    'Researcher tools for comparing mutation observations across selected genes and cancer cohorts.',
    'RESEARCHER TOOLS'
);
?>

<section class="analytics-intro">
    <div>
        <span class="overline">CROSS-COHORT EXPLORATION</span>
        <h2>Turn genomic records into interpretable patterns.</h2>
        <p>Choose genes to compare their recorded mutations, or inspect counts by cohort and classification.</p>
    </div>
    <span class="analysis-mark">✳</span>
</section>

<!-- Researcher-only assignment requirement:
     "Visualise the number of mutations in a selected list of genes." -->
<section class="panel">
    <div class="panel-title">
        <div>
            <h2>Mutations in selected genes</h2>
            <p>Select up to 12 genes to compare their mutation observation counts.</p>
        </div>
        <span class="mini-tag">RESEARCHER ONLY</span>
    </div>

    <form id="selectedGenesForm" class="selected-genes-form">
        <fieldset class="gene-choice-fieldset">
            <legend>Frequently observed genes</legend>
            <div class="gene-choice-grid">
                <?php foreach ($geneChoices as $index => $gene): ?>
                    <label class="gene-choice">
                        <input
                            type="checkbox"
                            name="selectedGene"
                            value="<?=h($gene['GeneSymbol'])?>"
                            <?=$index < 4 ? 'checked' : ''?>
                        >
                        <span><?=h($gene['GeneSymbol'])?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="gene-compare-form">
            <label for="extraGeneSymbols">Additional genes (optional)</label>
            <div class="search-inner">
                <?=icon('search')?>
                <input
                    id="extraGeneSymbols"
                    type="text"
                    maxlength="700"
                    placeholder="e.g. TP53, KRAS, DNMT3A"
                    aria-describedby="selectedGenesHint"
                >
            </div>
            <button type="submit" class="button primary">
                Visualise selection <?=icon('arrow')?>
            </button>
        </div>
        <p id="selectedGenesHint" class="small-help">
            Tick genes above, add comma-separated gene symbols, or both.
            Repeated symbols count once.
        </p>
    </form>

    <div id="selectedGenesStatus" class="results-summary" role="status">
        Preparing gene comparison…
    </div>
    <div id="selectedGenesChart" class="chart-slot" aria-label="Mutation counts for selected genes">
        Loading chart…
    </div>
    <p class="small-help">
        Counts represent recorded mutation observations in SampleMutation,
        not the number of unique patients or a clinical mutation frequency.
    </p>
</section>

<!-- Existing overview charts from main, kept intact. -->
<div class="dashboard-grid">
    <section class="panel">
        <div class="panel-title">
            <div>
                <h2>Mutations by cohort</h2>
                <p>Number of observations in each cancer category</p>
            </div>
        </div>
        <div class="chart-slot" id="mutationCohortChart">Loading chart…</div>
    </section>
    <section class="panel">
        <div class="panel-title">
            <div>
                <h2>Mutation classifications</h2>
                <p>Most common annotated types</p>
            </div>
        </div>
        <div class="chart-slot" id="classChart">Loading chart…</div>
    </section>
</div>

<!-- Existing single-gene comparison across six cancer cohorts. -->
<section class="panel">
    <div class="panel-title">
        <div>
            <h2>Gene-level cohort comparison</h2>
            <p>Find how frequently one gene is observed across the six cancer cohorts.</p>
        </div>
    </div>

    <form class="gene-compare-form" id="compareForm">
        <label for="compareGene">Gene symbol</label>
        <div class="search-inner">
            <?=icon('search')?>
            <input id="compareGene" name="gene" maxlength="100" placeholder="e.g. TP53" value="TP53">
        </div>
        <button type="submit" class="button primary">
            Compare gene <?=icon('arrow')?>
        </button>
    </form>

    <div class="chart-slot" id="geneCompareChart">Select a gene.</div>
    <p class="small-help">
        Raw observation counts are not adjusted for cohort size, sequencing coverage or gene length.
    </p>
</section>

<script>
    window.addEventListener('DOMContentLoaded', function () {
        window.OncoCharts.loadAnalytics();
    });
</script>

<?php end_page(); ?>
