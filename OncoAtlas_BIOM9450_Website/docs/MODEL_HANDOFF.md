# Model integration — implemented

The handoff described in earlier versions of this file has been implemented by the predictive-analysis section.
See `PREDICTION.md` for the files, user flow, model details and tests.

Summary: `predict.php` runs the exported random forest in the browser (`assets/js/predict.js`).
`api/save_prediction.php` recomputes the result on the server (`app/model_predictor.php`) and saves
`Prediction`, `PredictionScore` and `PredictionMutation` in one transaction, logging `CREATE_PREDICTION`.
Then it redirects to `prediction.php?id=...`, where feedback is stored.
