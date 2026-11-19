# Database mapping — source `sample_data.sql`

No new schema is required. The website deliberately uses the exact names, capitalization and relationships from the supplied SQL dump.

| Database table / view | Key columns used | Portal functionality |
|---|---|---|
| `CancerType` | `CancerTypeID`, `CancerName`, `StudyCode`, `DiagnosisLabel` | Cohort filters, cohort cards, prediction labels |
| `Patient` | `PatientID`, `TCGAPatientID`, `Name`, `Photo`, `Age`, `Sex`, `Country`, `Treatment`, `Diagnosis`, `CancerTypeID`, `DemographicsSynthetic`, `UserID`, `OncologistID` | Search, patient details, create demonstration patient, role restrictions |
| `TumorSample` | `SampleID`, `PatientID`, `TCGASampleBarcode`, `SampleType` | Patient–sample relationship, mutation import |
| `Gene` | `GeneID`, `GeneSymbol`, `Description` | Gene explorer and mutation joins |
| `Variant` | `VariantID`, `GenomeBuild`, `Chromosome`, `MutationPosition`, `EndPosition`, `ReferenceAllele`, `AlternateAllele`, `VariantKey` | Chromosomal filters, exact variants |
| `SampleMutation` | `SampleMutationID`, `SampleID`, `GeneID`, `VariantID`, `MutationType`, `VariantType`, `Impact`, `ProteinChange`, `SourceRow` | Mutation explorer, imported mutation observations |
| `AppUser` | `UserID`, `Name`, `Email`, `Password`, `Role` | Login/roles/change password |
| `Professional` | `ProfessionalID`, `UserID`, `Specialty`, `Institution` | Clinicians/researchers page and patient assignment |
| `Prediction` | `PredictionID`, `PatientID`, `CreatedBy`, `ModelVersion`, `CreatedAt` | Read-only future prediction records |
| `PredictionScore` | `PredictionID`, `CancerTypeID`, `Score` | Read-only prediction score display |
| `PredictionMutation` | `PredictionID`, `SampleMutationID` | Future model provenance |
| `Feedback` | `FeedbackID`, `PredictionID`, `UserID`, `IsAccurate`, `Comments`, `CreatedAt` | Working feedback mechanism for existing predictions |
| `AuditLog` | `AuditID`, `UserID`, `Action`, `EntityType`, `EntityID`, `CreatedAt` | Audit and activity page |
| `ModelInput` | Source mutation fields from `Patient`, `TumorSample`, `Gene`, `Variant`, `SampleMutation` | Model handoff view; not invoked yet |

## Relationships used by the website

`CancerType` (1) → (many) `Patient` → (many) `TumorSample` → (many) `SampleMutation`.

Each `SampleMutation` links to one `Gene` and one `Variant`. `AppUser` and `Professional` have a one-to-one relation for professional accounts. `Patient.UserID` optionally links a patient record to a patient account. `Prediction` belongs to a patient and creator; one prediction may have multiple cancer scores and feedback entries.

The database already contains synthetic patient display demographics and genuine TCGA-derived mutation observations; no source data was rewritten in this website build. Newly created demonstration patients are explicitly marked synthetic. Do not interpret the supplied names, ages, countries or treatments as verified clinical data.
