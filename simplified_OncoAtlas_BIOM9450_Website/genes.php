<?php
// Gene explorer: display search and cohort filters. JavaScript asks api/genes.php for gene-level counts and shows the returned records.

require_once __DIR__.'/app/layout.php';require_professional();audit('VIEW_GENES','Gene',null);
start_page('Gene explorer','genes.php','Find gene symbols, count their observed mutations and identify affected cohorts.','GENOMIC DATA');
?>
<section class="panel">
<div class="filters">
<div class="search-field">
<label for="geneSearch">Search gene symbols</label>
<div class="search-inner">
<?=icon('search')?>
<input type="search" id="geneSearch" placeholder="Try TP53, KRAS, DNMT3A or IDH1" maxlength="100">
</div>
</div>
<div class="filter-field">
<label for="geneCohort">Cancer cohort</label>
<select id="geneCohort">
<option value="">All cohorts</option>
<?php cohort_options();?>
</select>
</div>
<button type="button" class="button subtle" id="geneReset">Clear</button>
</div>
<div class="results-summary" id="geneStatus" role="status">Loading genes…</div>
<div class="table-scroll">
<table>
<thead>
<tr>
<th>Gene symbol</th>
<th>Mutation observations</th>
<th>Distinct samples</th>
<th>Distinct patients</th>
<th>
</th>
</tr>
</thead>
<tbody id="geneBody">
<tr>
<td colspan="5">Loading…</td>
</tr>
</tbody>
</table>
</div>
<div class="pagination">
<button type="button" class="button subtle" id="genesPrev">← Previous</button>
<span id="genesPage">Page 1</span>
<button type="button" class="button subtle" id="genesNext">Next →</button>
</div>
</section>
<?php if(is_researcher()):?>
<div class="footer-note">Researchers can also compare gene counts across cancer cohorts in <a href="analytics.php">Analytics</a>.</div>
<?php endif;?>
<script>window.addEventListener('DOMContentLoaded',()=>window.OncoTables?.genes());</script>
<?php end_page();
