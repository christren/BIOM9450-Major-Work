# Model integration handoff — intentionally not implemented

The web application **does not call, train or simulate a prediction model**. The teammate responsible for machine learning can add that functionality later without changing the database schema.

## Existing model-facing data

- `ModelInput` view: each imported mutation row includes `SampleMutationID`, `PatientID`, `Tumor_Sample_Barcode`, `Hugo_Symbol`, `Variant_Classification`, `Variant_Type`, alleles, genomic coordinates and protein change.
- `CancerType`: six classes and their `CancerTypeID`, `StudyCode`, `DiagnosisLabel`.
- `Prediction`: (`PredictionID`, `PatientID`, `CreatedBy`, `ModelVersion`, `CreatedAt`).
- `PredictionScore`: (`PredictionID`, `CancerTypeID`, `Score`) with values 0–1.
- `PredictionMutation`: (`PredictionID`, `SampleMutationID`) for source observations informing a result.
- `Feedback`: (`FeedbackID`, `PredictionID`, `UserID`, `IsAccurate`, `Comments`, `CreatedAt`).

## Suggested backend contract

The future prediction endpoint might accept `POST` with `csrf_token`, `patient_id`, and a selected `sample_id`. The backend must verify the current user is an authorised professional, that the selected sample belongs to the patient, and that the model has genuinely generated output. Avoid allowing arbitrary users to insert scores without a genuine prediction.

Model output should identify a *model version* and six class scores keyed by `CancerType.StudyCode`. The model owner must confirm whether scores are calibrated probabilities or uncalibrated classifier outputs, and display them accurately.

**After a real model prediction**, store the result in one transaction:

1. `INSERT INTO Prediction (PatientID,CreatedBy,ModelVersion) VALUES (?,?,?)`.
2. For each class, resolve `CancerTypeID` via the study code and `INSERT INTO PredictionScore (PredictionID,CancerTypeID,Score) VALUES (?,?,?)`.
3. For any contributing `SampleMutationID` values, insert them into `PredictionMutation`.
4. Commit and log an `AuditLog` action `CREATE_PREDICTION`.
5. Redirect to `prediction.php?id=...` which **already displays stored scores** and provides a working feedback form.

This handoff is a design sketch, **not** a fitted model, clinical validation, or a claim that the prediction pipeline is complete.
