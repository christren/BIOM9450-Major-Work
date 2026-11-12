# BIOM9450 TCGA demonstration database

## Purpose and scope
MySQL 8.0.16+ / InnoDB database for the major-work portal, matching the PHP/PDO style of Assignment 3. This is a schema/data starter, NOT a completed portal. No SQLite .db is needed. Import the SQL file into MySQL.

Contains 300 real TCGA patients (50 per study), 300 samples, 24,513 mutation rows, 11,673 genes and 24,140 distinct genomic variants. Sources: AML (laml), lower-grade glioma (lgg), breast (brca), liver (lihc), pancreas (paad), prostate (prad), all PanCancer Atlas studies as supplied by the student. CancerName uses the model's labels Blood/Brain/Breast/Liver/Pancreas/Prostate; DiagnosisLabel retains the more specific disease description. Sampling seed 42; source checksums in source_manifest.json.

## Real versus synthetic
Real: TCGA IDs, sample barcodes, gene symbols, mutation locations/alleles/annotations, and study-based cancer diagnoses.
Synthetic: display names, ages, sexes, country, treatment text, professional details, and demo accounts. ALL Patient rows have DemographicsSynthetic=1. Synthetic sex does not establish original biological sex. Diagnosis is the real study-level label, not an invented clinical assessment. CollectionDate and Photo are NULL (no invented collection dates or missing image files). The UI must show a synthetic-demographics label and an image placeholder. None of these data are for clinical use.

## Import (new empty database only)
1. Start MySQL. In MySQL Workbench execute:

```sql
CREATE DATABASE biom9450_cancer CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
```

2. Open sql/cancer_portal.sql in Workbench, set biom9450_cancer as the default schema (double-click it), and execute the script. Alternatively, in the MySQL command-line client:

```sql
USE biom9450_cancer;
SOURCE /absolute/path/to/sql/cancer_portal.sql;
```

The dump deliberately has NO CREATE DATABASE, USE, DROP TABLE or disabled foreign-key checks. It targets the database you select. Import ONCE into an EMPTY database. It is NOT an update/migration script; do not rerun over an existing installation. DDL is not rolled back by the data transaction: if an import fails, inspect the error and retry in a separate NEW empty database. Do not delete a database containing work you need.

3. Copy config.example.php to config.php and enter your local MySQL host, port, database and credentials. Keep config.php out of Git. Prefer a dedicated application account with SELECT/INSERT/UPDATE/DELETE access only to this database, not your MySQL administrator account.
4. Enable PHP PDO and pdo_mysql. From this package folder run:

```bash
php check_database.php
```

Expected counts are above and it should print `Demo password verified.` This is CLI-only, not a browser endpoint.
5. Run sql/example_queries.sql with the database selected. Each cancer must show 50 patients and orphan links must show zero.

## Demo application accounts
| Email | Role | Password |
|---|---|---|
| oncologist@example.test | Oncologist | DemoCancer!2026 |
| researcher@example.test | Researcher | DemoCancer!2026 |
| patient@example.test | Patient | DemoCancer!2026 |

These are APPLICATION accounts, not MySQL users. The database stores bcrypt hashes in AppUser.Password (the original column name retained). The demo patient account maps to PatientID=1; all patients are assigned to ProfessionalID=1. Accounts alone do not implement login or role checks. Use PHP password_verify on login and password_hash with PASSWORD_DEFAULT for new passwords. These publicly documented demo credentials are only for a local class demonstration.

```php
require __DIR__ . '/db.php';
$stmt = $pdo->prepare('SELECT * FROM AppUser WHERE Email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
if ($user && password_verify($password, $user['Password'])) {
    // Regenerate the session ID and store the authenticated ID/role.
    // Implement access checks on every protected page/API.
}
```

## Tables and relationships
The eight original table names are retained, with deliberate additions/normalisation:
- CancerType: model label, study code and specific diagnosis label.
- AppUser: name, unique email, password HASH and role.
- Professional: one professional record per user; specialty/institution.
- Patient: integer internal ID, unique original TCGAPatientID, display demographics, diagnosis, CancerTypeID, optional user account and assigned professional.
- TumorSample: sample barcode and PatientID (one patient can have several samples).
- Gene: unique GeneSymbol.
- Variant: genomic identity = build + chromosome + start + end + reference + alternate allele. VariantKey is a SHA-256 identity key to support long indels without truncating alleles.
- SampleMutation: joins sample/gene/variant; also stores MutationType (=Variant_Classification), VariantType, Impact and ProteinChange. Annotations were moved here from the original Variant draft because the same genomic locus can have different gene/transcript annotations. SourceRow is a unique provenance row number, not a clinical attribute. All original rows are kept, including duplicates; no UNIQUE(sample,gene,variant) restriction silently drops source records.

Additional empty tables support specification requirements:
- Prediction -> Patient, AppUser; ModelVersion is required.
- PredictionScore -> Prediction, CancerType (one score per cancer). Application must write six scores summing approximately to 1 in a transaction.
- PredictionMutation -> Prediction, SampleMutation: snapshots the exact INPUT ROW IDs, not immutable feature values.
- Feedback -> Prediction, AppUser; reaches exact mutations through PredictionMutation.
- AuditLog -> optional AppUser: application must INSERT events for viewing/editing/reporting/login as required. No automatic audit coverage is claimed.

No predictions or feedback are fabricated. When storing a prediction, use a transaction to create Prediction, all scores and all input links. The application must check that linked mutations belong to that prediction's patient (cross-table ownership is NOT enforced by these simple foreign keys). Feedback authors must be authorised. Do not edit/delete referenced source mutations after prediction if you require historical reproducibility; a production system needs immutable versioned input snapshots. Nullable dates/fields represent unknown information. Integer primary keys use AUTO_INCREMENT so PHP can insert new records.

## Model input / portal usage
ModelInput is a joined SQL VIEW that exposes original training column names, including barcode, gene, classification, variant type and reference/alternate alleles, plus genomic coordinates. Query by PatientID using a prepared statement, and export those rows as tab-delimited input.

```php
$stmt = $pdo->prepare('SELECT * FROM ModelInput WHERE PatientID = ? ORDER BY SampleMutationID');
$stmt->execute([$patientId]);
$rows = $stmt->fetchAll();
```

Do NOT feed age, sex, synthetic demographics, diagnosis or CancerName to the model. Reproduce the exact Python feature-building rules (including its patient-level deduplication) before JS inference. The original training script deduplicated without genomic coordinates; this database deliberately preserves genomic detail rather than imitating that lossy storage. Group by patient for inference; do not classify a single mutation as a whole patient. SourceRow preserves row order/provenance; repeated annotations may need the same preprocessing as training. Model weights and JS prediction logic are NOT bundled in this database package.

The 300 patients are from the original training studies and may include patients previously used in training/testing. This is a portal demonstration subset, NOT a new independent evaluation cohort. Do not retrain or report new generalisation claims from this subset.

## Specification alignment and remaining work
Supports required patient, cancer, mutation and researcher/oncologist entities; relational constraints; chromosome/gene/location filters and gene-count queries; sample/profile uploads; prediction-linked feedback and an audit storage structure. Your group still needs to implement login/session/roles, UI/forms, upload validation, model integration, JS charts, feedback handling, logging calls, and error handling. A database package alone does not complete those marking sections.

## Validation and limitations
Python verified source counts, 50 patients per cancer, patient/sample identity links, full retention of 24,513 mutation rows, gene/variant mapping round-trip and a separate in-memory relational foreign-key check. The bcrypt hash was checked with system crypt. See VALIDATION.json.

A MySQL server and PHP runtime were NOT available in the build environment: this SQL has NOT been imported into MySQL here, and the PHP files/password_verify must be tested locally. In-memory relational checking is not a substitute for a MySQL restore test. Run check_database.php and example_queries.sql before sharing a claim that the package works on your machine. Collation is case-sensitive for identifiers, so use the table/column spelling shown. SQL import uses normal MySQL backslash-escape mode (not NO_BACKSLASH_ESCAPES). No actual photos or clinical patient files were supplied.

## Files
- sql/cancer_portal.sql: schema and populated data (no destructive drops).
- sql/example_queries.sql: count, profile, gene and integrity queries.
- db.php / config.example.php: shared PHP PDO connection.
- check_database.php: local CLI connection/count/password check.
- source_manifest.json / VALIDATION.json: provenance and validation evidence.
- .gitignore: excludes local credentials/logs.

Place this folder in your repository as biom_project/database, then commit using GitHub Desktop. No changes to the full TCGA datasets or model are required. Generated with AI assistance; review, test and acknowledge assistance according to course requirements.
