# OncoAtlas — BIOM9450 Cancer Research Portal

A functioning **PHP 8 + MySQL** research web application built around the exact tables in the provided `sample_data.sql` file. It uses custom HTML/CSS, vanilla JavaScript and AJAX (Fetch API). **The machine-learning model is intentionally not included.** The prediction screens read saved results when a teammate eventually integrates a trained model; they do not invent scores.

**Important:** This is an academic demonstration, not a diagnostic or clinical system. The uploaded SQL explicitly states that its mutation observations come from TCGA while its patient display demographics are **synthetic**. The website flags synthetic demographics rather than presenting them as verified clinical data.

## 1. Included files

```text
OncoAtlas_BIOM9450_Website/
├── index.php                  Homepage redirect
├── router.php                 Security router for PHP built-in dev server
├── login.php / logout.php     Password-based session authentication
├── dashboard.php              Dashboard + live MySQL/AJAX visualisations
├── patients.php               Searchable, filterable patient directory
├── patient.php                Patient profile, samples and mutations
├── my_profile.php             Restricted patient-account view
├── add_patient.php            Add demonstration patient + optional photo
├── mutations.php              AJAX filters (gene, cohort, chr, position, etc.)
├── genes.php                  AJAX gene explorer
├── cohorts.php                Six cancer types and their record counts
├── professionals.php          Researcher/oncologist profiles
├── analytics.php              Researcher-only AJAX charts and comparison
├── upload.php                 Small TSV mutation imports (transactional)
├── predictions.php            Saved prediction history (future model)
├── prediction.php             Existing scores and feedback form
├── feedback.php               Store feedback on existing predictions
├── activity.php               Audit entries for the current account
├── account.php                Change your own account password
├── app/
│   ├── bootstrap.php          PDO, roles, CSRF, session, validation, audit
│   └── layout.php             Reusable sidebar, header, footer, icons
├── api/
│   ├── patients.php           Paginated patient records
│   ├── mutations.php          Paginated mutation search
│   ├── genes.php              Paginated gene statistics
│   ├── patient_samples.php    Sample autocomplete for uploads
│   └── stats.php              JSON chart and cohort comparison data
├── assets/
│   ├── css/style.css          Responsive custom CSS (no Bootstrap)
│   ├── js/app.js              AJAX, tables, charts, UI
│   └── uploads/               Optional, restricted demo photos
├── config/config.php          Local MySQL connection settings
├── database/sample_data.sql   ORIGINAL PROVIDED SQL; UNCHANGED
├── database/upload_example.tsv A synthetic TSV for import testing
├── tools/setup_demo_passwords.php
└── docs/
    ├── preview_login_desktop.png  Login design preview
    ├── preview_login_mobile.png   Mobile design preview
    ├── MANUAL_TESTS.md
    ├── MODEL_HANDOFF.md
    └── DATABASE_MAP.md
```

## 2. Requirements

- PHP 8.1+ with **pdo_mysql**, `session`, `fileinfo` and `json` extensions enabled
- MySQL 8.0+ (foreign keys and CHECK constraints)
- Apache / MAMP / XAMPP / PHP development server (PHP must handle `.php` files)
- A modern browser; charts work locally without any external chart libraries or internet access

Check which PHP binary is running: `php -v`. Check MySQL driver: `php -m | grep -i pdo_mysql`.

## 3. Quick setup (new empty database)

1. **Extract the ZIP** into the document root of your local PHP web server. With XAMPP on macOS, a typical folder is `/Applications/XAMPP/xamppfiles/htdocs/OncoAtlas_BIOM9450_Website/`. With MAMP, use its configured `htdocs` directory. Alternatively, use a normal local folder with `php -S` (step 5).
2. **Start MySQL**. Import `database/sample_data.sql` **once into a NEW, EMPTY database**. It creates `cancer_portal` itself and populates all existing data, including six cancer types. In MySQL Workbench: *File → Open SQL Script*, choose `database/sample_data.sql`, then execute all statements. Or from a terminal in the extracted website folder:

   ```bash
   mysql -u root -p < database/sample_data.sql
   ```

   Do not rerun the dump in a populated `cancer_portal` database; it contains plain `CREATE TABLE` and `INSERT` statements, which will conflict with existing tables/data. **The included source SQL was not rewritten.**

3. Open `config/config.php` and change `db_host`, `db_port`, `db_user`, and `db_pass` to match **your own local MySQL connection**. The file ships with common local defaults (`127.0.0.1`, port `3306`, user `root`, blank password). A blank password is only appropriate for an isolated, local educational environment.
4. **Generate login passwords** for the three accounts already present in your SQL file. From the extracted project directory, run:

   ```bash
   php tools/setup_demo_passwords.php
   ```

   This updates *only the `Password` hash for the existing sample user accounts*. It prints three newly generated passwords **once**; keep them private. Do not paste them into reports or public repositories. If your terminal `php` lacks `pdo_mysql`, use your local web stack’s PHP CLI executable instead, e.g. `/Applications/XAMPP/xamppfiles/bin/php tools/setup_demo_passwords.php` if that matches your installation.

5. **Open the site:** in XAMPP/MAMP go to `http://localhost/OncoAtlas_BIOM9450_Website/` (adjust the host/port as required). Or run `php -S 127.0.0.1:8000 router.php` in the project directory and open `http://127.0.0.1:8000/`.
6. Sign in with one of the sample emails below and its **generated** password:

   | Role | Sample email | Access |
   |---|---|---|
   | Oncologist | `oncologist@example.test` | Patients, mutation search, upload, cohorts |
   | Researcher | `researcher@example.test` | Same as oncologist, plus analytics |
   | Patient | `patient@example.test` | Only linked profile and prediction history |

The source SQL contains existing bcrypt hashes, but their plaintext passwords were **not supplied**. The setup script deliberately replaces demo account password hashes rather than pretending to know those passwords.

## 4. Feature walkthrough

**Overview:** Live totals calculated from MySQL, two charts fetched via `api/stats.php`, and recent patient links.

**Patients:** AJAX search by name/TCGA identifier, cohort/sex filters and pagination. Click a patient to see details, linked samples, most observed genes, and recent mutations.

**Add patient:** Professionals can create a new **demonstration** patient. The original TCGA records are not edited; a new patient is inserted with `TCGAPatientID=NULL` and `DemographicsSynthetic=1`. An optional JPG/PNG/WebP image (≤2MB) can be stored in `assets/uploads`.

**Mutations:** AJAX filtering by gene, cohort, chromosome, genomic start position, mutation classification, sample barcode and patient ID. The result table joins six tables and supports pagination.

**Genes:** AJAX gene search and mutation, sample and patient counts. **Researcher analytics** compares gene counts across the six types of cancer.

**Import mutations:** For an existing or newly created patient, enter a sample barcode (either an existing barcode belonging to that patient or a new one), choose a `.tsv`/`.txt` with cBioPortal-like tab-delimited columns, then import. The importer validates all rows, uses prepared statements and an exclusive MySQL advisory lock, and commits the whole batch or none. Limit: 5 MB and 1,000 rows per file. This is a **small demonstration importer**, not a replacement for the supplied full-cohort SQL dump. Duplicate sample/gene/variant/classification/protein combinations are skipped; source rows already present are not modified. For a test, change the example TSV’s barcode to match your form selection.

**Predictions:** Only displays results already stored in `Prediction`, `PredictionScore`, and `PredictionMutation`. An explanatory integration panel is shown while the ML system is unavailable. Feedback is available only for real, saved prediction records.

**Security:** Prepared PDO queries, HTML output escaping, session regeneration, bcrypt passwords, CSRF form tokens, file validation, role checks and audit records. `activity.php` displays recent actions for the current professional account.

## 5. Important limits

- There is **no machine-learning model**, no prediction endpoint and no fake prediction confidence output.
- Predictions and feedback require a teammate to store actual results first. See `docs/MODEL_HANDOFF.md`.
- This project is an academic demonstration using TCGA research data and synthetic display demographics, **not** suitable for real patient records or public deployment without extensive security/privacy review.
- The `Photo`, `Country`, `Treatment`, `Age` and `Sex` fields in the supplied demo records are synthetic and should not be interpreted as original TCGA clinical measurements.
- PHP and MySQL must be configured locally; this package is source code, not a hosted website.
- Apache `.htaccess` rules restrict direct access to `config`, `database`, `docs`, `tools` and uploads. **Nginx ignores `.htaccess`**, so those folders must be denied in the server config or kept outside the public root before deployment. PHP's built-in server does not enforce `.htaccess`; use the bundled `router.php` when running it for local development.

## 6. Assignment references

- Data: your uploaded `sample_data.sql` (copied byte-for-byte to `database/sample_data.sql`).
- Programme: BIOM9450, Web-Based Cancer Type Prediction System.
- Scope: functioning website and SQL integration; teammate's ML model to be integrated later.
- Test checklist: `docs/MANUAL_TESTS.md`.
