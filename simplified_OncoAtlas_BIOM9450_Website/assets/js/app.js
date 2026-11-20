'use strict';

// Find an HTML element by its id, e.g. element('patientsBody').
function element(id) {
    return document.getElementById(id);
}

// Escape text that came from MySQL before putting it into innerHTML.
// This stops data such as <script> from being treated as HTML.
function safeText(value) {
    let text = String(value ?? '');
    return text.replace(/[&<>"']/g, function (character) {
        const replacements = {
            '&': '&amp;', '<': '&lt;', '>': '&gt;',
            '"': '&quot;', "'": '&#39;'
        };
        return replacements[character];
    });
}

// Show a dash when an optional database field is empty.
function showValue(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }
    return safeText(value);
}

function showNumber(value) {
    return Number(value || 0).toLocaleString('en-AU');
}

// Wait until typing stops before sending an AJAX request.
// Without this, every keystroke would create a new MySQL request.
function afterTyping(callback) {
    let timer;
    return function () {
        clearTimeout(timer);
        timer = setTimeout(callback, 300);
    };
}

// GET JSON from a PHP endpoint
async function getJSON(url) {
    const response = await fetch(url, {credentials: 'same-origin'});

    if (response.status === 401) {
        window.location.href = 'login.php';
        throw new Error('Your session expired. Please sign in again.');
    }

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || 'Could not load data.');
    }

    return data;
}

// Shared table loader for the Patients, Mutations and Genes pages.
// Each page supplies the endpoint, form controls and how to show one row.
function setupTable(settings) {
    const tableBody = element(settings.bodyId);
    const status = element(settings.statusId);
    const pageLabel = element(settings.pageId);
    const previous = element(settings.prevId);
    const next = element(settings.nextId);

    if (!tableBody) return null;

    let page = 1;
    let totalPages = 1;
    let lastRequest = 0;

    async function load() {
        // Read current filter input values from the page.
        const parameters = new URLSearchParams(settings.getFilters());
        parameters.set('page', String(page));

        const request = ++lastRequest;
        status.textContent = 'Loading…';
        tableBody.setAttribute('aria-busy', 'true');

        try {
            const url = settings.endpoint + '?' + parameters.toString();
            const result = await getJSON(url);

            // If an older request finishes after a newer one, ignore it.
            if (request !== lastRequest) return;

            page = Number(result.page);
            totalPages = Number(result.pages);

            // Convert each JSON row to the table's HTML.
            let html = '';
            for (const record of result.rows) {
                html += settings.makeRow(record);
            }

            if (result.rows.length === 0) {
                html = '<tr><td class="no-results" colspan="' +
                    settings.columns +
                    '">No matching records. Try different filters.</td></tr>';
            }

            tableBody.innerHTML = html;
            status.textContent = showNumber(result.total) + ' matching records';
            pageLabel.textContent = 'Page ' + page + ' of ' + totalPages;
            previous.disabled = page <= 1;
            next.disabled = page >= totalPages;

        } catch (error) {
            if (request !== lastRequest) return;
            status.textContent = error.message;
            tableBody.innerHTML = '<tr><td class="no-results" colspan="' +
                settings.columns + '">Could not load results.</td></tr>';
        } finally {
            if (request === lastRequest) tableBody.removeAttribute('aria-busy');
        }
    }

    // A changed search starts again from the first page.
    function refresh() {
        page = 1;
        load();
    }

    previous.addEventListener('click', function () {
        if (page > 1) {
            page--;
            load();
        }
    });

    next.addEventListener('click', function () {
        if (page < totalPages) {
            page++;
            load();
        }
    });

    load();
    return {refresh: refresh};
}

// PATIENTS — API: api/patients.php
function initialisePatients() {
    const form = element('patientsFilters');
    if (!form) return;

    const table = setupTable({
        endpoint: 'api/patients.php',
        bodyId: 'patientsBody',
        statusId: 'patientsStatus',
        pageId: 'patientsPage',
        prevId: 'patientsPrev',
        nextId: 'patientsNext',
        columns: 8,
        getFilters: function () {return new FormData(form);},
        makeRow: function (patient) {
            const id = Number(patient.PatientID);
            let tag = '';
            if (Number(patient.DemographicsSynthetic)) {
                tag = '<small class="table-subline">Teaching profile</small>';
            }

            return '<tr>' +
                '<td><strong>' + safeText(patient.Name) + '</strong>' + tag + '</td>' +
                '<td class="mono">' + showValue(patient.TCGAPatientID) + '</td>' +
                '<td><span class="code-pill">' + showValue((patient.StudyCode || '').toUpperCase()) + '</span></td>' +
                '<td>' + showValue(patient.Age) + '</td>' +
                '<td>' + showValue(patient.Sex) + '</td>' +
                '<td>' + showNumber(patient.Samples) + '</td>' +
                '<td>' + showNumber(patient.Mutations) + '</td>' +
                '<td><a class="row-link" href="patient.php?id=' + id + '">Open ›</a></td>' +
                '</tr>';
        }
    });

    form.addEventListener('input', afterTyping(table.refresh));
    form.addEventListener('change', table.refresh);
    element('resetPatients').addEventListener('click', function () {
        form.reset();
        table.refresh();
    });
}

// MUTATIONS — API: api/mutations.php
function initialiseMutations() {
    const form = element('mutationFilters');
    if (!form) return;

    const table = setupTable({
        endpoint: 'api/mutations.php',
        bodyId: 'mutationsBody',
        statusId: 'mutationStatus',
        pageId: 'mutationsPage',
        prevId: 'mutationsPrev',
        nextId: 'mutationsNext',
        columns: 10,
        getFilters: function () {return new FormData(form);},
        makeRow: function (mutation) {
            const gene = String(mutation.GeneSymbol || '');
            const id = Number(mutation.PatientID);
            const impact = String(mutation.Impact || '').toLowerCase();

            return '<tr>' +
                '<td><a class="gene-link" href="genes.php?gene=' + encodeURIComponent(gene) + '">' + safeText(gene) + '</a>' +
                '<small class="table-subline"><a href="shared_patients.php?mode=gene&gene=' + encodeURIComponent(gene) + '">Same gene</a></small></td>' +
                '<td>' + showValue(mutation.Chromosome) + '</td>' +
                '<td class="mono">' + showValue(mutation.MutationPosition) +
                '<small class="table-subline"><a href="shared_patients.php?mode=position&chr=' + encodeURIComponent(mutation.Chromosome || '') +
                '&position=' + encodeURIComponent(mutation.MutationPosition || '') + '&build=' + encodeURIComponent(mutation.GenomeBuild || '') +
                '">Same position</a></small></td>' +
                '<td class="mono variant-alleles">' + showValue(mutation.ReferenceAllele) + ' → ' + showValue(mutation.AlternateAllele) + '</td>' +
                '<td>' + showValue(mutation.MutationType) + '</td>' +
                '<td><span class="impact ' + safeText(impact) + '">' + showValue(mutation.Impact) + '</span></td>' +
                '<td class="mono">' + showValue(mutation.ProteinChange) + '</td>' +
                '<td><a class="mono sample-link" href="patient.php?id=' + id + '">' + showValue(mutation.TCGASampleBarcode) + '</a></td>' +
                '<td><a class="row-link" href="patient.php?id=' + id + '">' + showValue(mutation.PatientName) + '</a></td>' +
                '<td><span class="code-pill">' + showValue((mutation.StudyCode || '').toUpperCase()) + '</span></td>' +
                '</tr>';
        }
    });

    form.addEventListener('input', afterTyping(table.refresh));
    form.addEventListener('change', table.refresh);
    element('clearMutations').addEventListener('click', function () {
        form.reset();
        form.elements.patient.value = '';
        table.refresh();
    });
}

// GENES — API: api/genes.php
function initialiseGenes() {
    const search = element('geneSearch');
    const cohort = element('geneCohort');
    if (!search) return;

    // A gene link from the Mutation explorer arrives as ?gene=TP53.
    const params = new URLSearchParams(window.location.search);
    if (params.has('gene')) search.value = params.get('gene');

    const table = setupTable({
        endpoint: 'api/genes.php',
        bodyId: 'geneBody',
        statusId: 'geneStatus',
        pageId: 'genesPage',
        prevId: 'genesPrev',
        nextId: 'genesNext',
        columns: 5,
        getFilters: function () {
            return new URLSearchParams({q: search.value, cancer: cohort.value});
        },
        makeRow: function (gene) {
            return '<tr>' +
                '<td><strong class="gene-symbol">' + safeText(gene.GeneSymbol) + '</strong></td>' +
                '<td>' + showNumber(gene.MutationCount) + '</td>' +
                '<td>' + showNumber(gene.SampleCount) + '</td>' +
                '<td>' + showNumber(gene.PatientCount) + '</td>' +
                '<td><a class="row-link" href="mutations.php?gene=' +
                encodeURIComponent(gene.GeneSymbol) + '">View mutations ›</a></td>' +
                '</tr>';
        }
    });

    search.addEventListener('input', afterTyping(table.refresh));
    cohort.addEventListener('change', table.refresh);
    element('geneReset').addEventListener('click', function () {
        search.value = '';
        cohort.value = '';
        table.refresh();
    });
}

// CHARTS — create simple bars using HTML/CSS instead of an extra library.
const chartColours = ['#6258e8', '#009b96', '#ef9a47', '#4884dd', '#b56bd8', '#eb677d'];

function drawBars(container, rows, labelField, countField) {
    if (!container) return;
    container.innerHTML = '';

    if (rows.length === 0) {
        container.textContent = 'No chart data available.';
        return;
    }

    let maximum = 1;
    for (const record of rows) {
        maximum = Math.max(maximum, Number(record[countField] || 0));
    }

    const chart = document.createElement('div');
    chart.className = 'chart-bars';

    rows.forEach(function (record, index) {
        const label = String(record[labelField]);
        const count = Number(record[countField] || 0);
        const percentage = Math.min(100, count / maximum * 100);

        // Create chart elements with DOM functions (not unsafe HTML).
        const row = document.createElement('div');
        row.className = 'chart-row';

        const title = document.createElement('span');
        title.className = 'chart-label';
        title.textContent = label;
        title.title = label;

        const track = document.createElement('div');
        track.className = 'chart-track';

        const bar = document.createElement('div');
        bar.className = 'chart-bar';
        bar.style.width = percentage + '%';
        bar.style.backgroundColor = chartColours[index % chartColours.length];
        track.appendChild(bar);

        const value = document.createElement('strong');
        value.className = 'chart-number';
        value.textContent = showNumber(count);

        row.appendChild(title);
        row.appendChild(track);
        row.appendChild(value);
        chart.appendChild(row);
    });

    container.appendChild(chart);
}

// DASHBOARD CHARTS — AJAX response comes from api/stats.php?scope=overview.
async function loadDashboardCharts() {
    try {
        const data = await getJSON('api/stats.php?scope=overview');

        const cohorts = data.cohorts.map(function (row) {
            return {label: row.StudyCode.toUpperCase(), count: row.Patients};
        });
        const genes = data.topGenes.map(function (row) {
            return {label: row.GeneSymbol, count: row.Mutations};
        });

        drawBars(element('cohortChart'), cohorts, 'label', 'count');
        drawBars(element('geneChart'), genes, 'label', 'count');
    } catch (error) {
        if (element('cohortChart')) element('cohortChart').textContent = error.message;
        if (element('geneChart')) element('geneChart').textContent = error.message;
    }
}

// RESEARCHER CHARTS — AJAX requests for cohort / classification / gene counts.
async function loadResearchCharts() {
    try {
        const data = await getJSON('api/stats.php?scope=analytics');
        const cohortRows = data.cohorts.map(function (r) {
            return {label: r.StudyCode.toUpperCase(), count: r.Mutations};
        });
        const classRows = data.classes.map(function (r) {
            return {label: r.MutationType, count: r.Mutations};
        });

        drawBars(element('mutationCohortChart'), cohortRows, 'label', 'count');
        drawBars(element('classChart'), classRows, 'label', 'count');
    } catch (error) {
        if (element('mutationCohortChart')) {
            element('mutationCohortChart').textContent = error.message;
        }
    }

    const form = element('compareForm');
    if (!form) return;

    async function compare(event) {
        if (event) event.preventDefault(); // Do not reload the page.

        const gene = element('compareGene').value.trim();
        const output = element('geneCompareChart');

        if (gene === '') {
            output.textContent = 'Enter a gene symbol.';
            return;
        }

        output.textContent = 'Comparing cohorts…';
        try {
            const url = 'api/stats.php?scope=compare&gene=' + encodeURIComponent(gene);
            const data = await getJSON(url);
            const rows = data.rows.map(function (r) {
                return {label: r.StudyCode.toUpperCase(), count: r.Mutations};
            });
            drawBars(output, rows, 'label', 'count');
        } catch (error) {
            output.textContent = error.message;
        }
    }

    form.addEventListener('submit', compare);
    compare();

    // Separate assignment chart: multiple selected genes in one graph.
    initialiseSelectedGeneChart();
}

// Researcher-only: compare mutation observations for 1–12 selected genes.
// The PHP API checks the user's role AND validates the gene selection.
function initialiseSelectedGeneChart() {
    const form = element('selectedGenesForm');
    if (!form) return;

    const chart = element('selectedGenesChart');
    const status = element('selectedGenesStatus');
    const extraInput = element('extraGeneSymbols');

    async function visualiseGenes(event) {
        if (event) event.preventDefault();

        // Read checked boxes from the actual form (not hardcoded names).
        const selected = [];
        const checked = form.querySelectorAll('input[name="selectedGene"]:checked');

        for (const checkbox of checked) {
            selected.push(checkbox.value.trim().toUpperCase());
        }

        // An additional text input lets users compare other gene symbols.
        const extra = extraInput.value.split(',');
        for (const entry of extra) {
            const gene = entry.trim().toUpperCase();
            if (gene !== '') selected.push(gene);
        }

        // Remove duplicates, keeping the original selection order.
        const genes = [...new Set(selected)];

        if (genes.length < 1 || genes.length > 12) {
            status.textContent = 'Please select between 1 and 12 genes.';
            chart.textContent = '';
            return;
        }

        status.textContent = 'Loading selected genes…';
        chart.textContent = 'Preparing comparison…';

        try {
            const url = 'api/stats.php?scope=selected_genes&genes=' +
                encodeURIComponent(genes.join(','));
            const data = await getJSON(url);

            // The existing chart helper creates text nodes safely.
            const rows = data.rows.map(function (gene) {
                return {
                    label: gene.GeneSymbol,
                    count: gene.Mutations
                };
            });

            drawBars(chart, rows, 'label', 'count');

            let message = rows.length + ' selected gene(s) found.';
            if (data.notFound.length > 0) {
                message += ' Not found in database: ' + data.notFound.join(', ');
            }
            status.textContent = message;
        } catch (error) {
            status.textContent = error.message;
            chart.textContent = 'Unable to compare these genes.';
        }
    }

    form.addEventListener('submit', visualiseGenes);

    // Show the default four selected genes as soon as the page loads.
    visualiseGenes();
}

// UPLOAD ASSIST — show the existing samples when a patient ID is entered.
function initialiseSampleLookup() {
    const patientField = element('uploadPatient');
    const barcodeField = element('sampleBarcode');
    const output = element('sampleLookup');
    if (!patientField) return;

    async function showSamples() {
        const id = patientField.value.trim();
        output.innerHTML = '';

        if (!/^[0-9]+$/.test(id)) {
            output.textContent = 'Enter a patient ID to see existing samples.';
            return;
        }

        output.textContent = 'Looking up samples…';

        try {
            const url = 'api/patient_samples.php?patient_id=' + encodeURIComponent(id);
            const data = await getJSON(url);
            output.innerHTML = '';

            const name = document.createElement('p');
            name.textContent = data.patient.Name + ' · ' +
                (data.patient.TCGAPatientID || 'Manual profile');
            output.appendChild(name);

            if (data.samples.length === 0) {
                const message = document.createElement('small');
                message.textContent = 'No existing samples. Enter a new barcode.';
                output.appendChild(message);
                return;
            }

            const hint = document.createElement('small');
            hint.textContent = 'Click an existing sample to use its barcode:';
            output.appendChild(hint);

            for (const sample of data.samples) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'sample-choice';
                button.textContent = sample.TCGASampleBarcode || 'Sample #' + sample.SampleID;

                button.addEventListener('click', function () {
                    barcodeField.value = sample.TCGASampleBarcode || '';
                    barcodeField.focus();
                });
                output.appendChild(button);
            }
        } catch (error) {
            output.textContent = error.message;
        }
    }

    patientField.addEventListener('input', afterTyping(showSamples));
    if (patientField.value) showSamples();
}

// SHARED MENU — this is the simple mobile sidebar toggle.
document.addEventListener('DOMContentLoaded', function () {
    const menu = element('menuToggle');
    const sidebar = element('sidebar');

    if (menu && sidebar) {
        menu.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar) sidebar.classList.remove('open');
    });

    document.querySelectorAll('.nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (sidebar) sidebar.classList.remove('open');
        });
    });
});

// Pages already call these entry points. Keep the names so the existing
// HTML pages continue working without changes.
window.OncoTables = {
    patients: initialisePatients,
    mutations: initialiseMutations,
    genes: initialiseGenes
};
window.OncoCharts = {
    loadOverview: loadDashboardCharts,
    loadAnalytics: loadResearchCharts
};
window.OncoUpload = {
    init: initialiseSampleLookup
};
