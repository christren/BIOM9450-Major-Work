# BIOM9450 OncoAtlas — manual test checklist

These are **manual steps for you to perform after installing MySQL locally**. PHP/JS static syntax checks are performed during packaging, but end-to-end MySQL browser tests require a local MySQL server populated with the SQL dump.

| ID | Test | Expected result |
|---|---|---|
| T01 | Import `database/sample_data.sql` into a NEW empty MySQL server/database | All 13 tables and the `ModelInput` view appear; source rows are intact |
| T02 | Run `php tools/setup_demo_passwords.php`, then login with generated oncologist password | Dashboard opens; password verified using `password_verify`; audit logs LOGIN |
| T03 | Wrong password | Generic error; no access |
| T04 | Login as researcher and open Analytics | Researcher-only chart page works and displays values returned from `api/stats.php` |
| T05 | Login as oncologist and directly request `/analytics.php` | HTTP 403 |
| T06 | Search for a known TCGA ID on Patients page | AJAX table filters without a full page reload; click result to view patient and samples |
| T07 | On Mutation explorer filter by `TP53`, then by cohort | Mutation list and result count respond; pagination respects filters |
| T08 | Create a demonstration patient using Add patient | New profile appears with `TCGAPatientID=NULL`, `DemographicsSynthetic=1`; original source rows unchanged |
| T09 | On that profile, use Import mutations with example TSV | A new sample and mutation observation are created, or duplicates are skipped; audit has IMPORT_MUTATIONS |
| T10 | Upload a TSV with an invalid genomic coordinate or mismatched sample barcode | Import rejected; no partial rows saved |
| T11 | Log in as demo patient and request another patient's `patient.php?id=...` or `/api/mutations.php` | HTTP 403; only linked patient information accessible |
| T12 | Open Run prediction, choose patient #19 and wait for the result | Chart shows six scores; Breast predicted; "Held-out test patient" and "matches" chips shown |
| T13 | Click Save prediction, then submit feedback on the prediction page | Redirects to `prediction.php?id=…` with the same scores and "Based on 56 linked mutation records"; feedback appears after reload |
| T16 | On Run prediction, preview `biom_project/model_output_tcga_v2/examples/test_paad_TCGA-FB-A7DR.tsv` | Pancreas predicted; no Save button (preview only) |
| T17 | Preview a file with two patients, missing columns, or an empty file | Clear error message; no result shown |
| T18 | Log in as the patient account and open `predict.php` or `api/save_prediction.php` | HTTP 403 |
| T19 | Run the automated tests listed in `docs/PREDICTION.md` | All tests pass |
| T14 | Change password through My account | Existing password required; new hash stored; log-out/log-in uses new credentials |
| T15 | Resize to mobile width | Sidebar opens via menu; tables scroll horizontally; cards reflow |

### Example TSV for testing

Open `database/upload_example.tsv`, replace the sample barcode in the file with the value you enter on the upload form, then choose the patient ID of a **new demonstration** patient. The example observations are fictional and are for import functionality testing only.

### Suggested verification SQL

```sql
USE cancer_portal;
SELECT COUNT(*) FROM Patient;
SELECT COUNT(*) FROM TumorSample;
SELECT COUNT(*) FROM SampleMutation;
SELECT c.StudyCode, COUNT(DISTINCT p.PatientID) AS patients
FROM CancerType c LEFT JOIN Patient p ON p.CancerTypeID=c.CancerTypeID
GROUP BY c.StudyCode;
SELECT sm.SampleMutationID, p.Name, s.TCGASampleBarcode, g.GeneSymbol,
       v.Chromosome, v.MutationPosition
FROM SampleMutation sm
JOIN TumorSample s ON s.SampleID=sm.SampleID
JOIN Patient p ON p.PatientID=s.PatientID
JOIN Gene g ON g.GeneID=sm.GeneID
JOIN Variant v ON v.VariantID=sm.VariantID
LIMIT 10;
SELECT Action, COUNT(*) FROM AuditLog GROUP BY Action;
```
