-- 1. Show all patients and their cancer type
SELECT p.Name, c.CancerName
FROM Patient p
JOIN CancerType c ON p.CancerTypeID = c.CancerTypeID;

-- 2. Show all mutations for each patient
SELECT p.Name, g.GeneSymbol, sm.MutationType
FROM Patient p
JOIN TumorSample ts ON p.PatientID = ts.PatientID
JOIN SampleMutation sm ON ts.SampleID = sm.SampleID
JOIN Gene g ON sm.GeneID = g.GeneID;

-- 3. Find patients with TP53 mutations
SELECT DISTINCT p.Name
FROM Patient p
JOIN TumorSample ts ON p.PatientID = ts.PatientID
JOIN SampleMutation sm ON ts.SampleID = sm.SampleID
JOIN Gene g ON sm.GeneID = g.GeneID
WHERE g.GeneSymbol = 'TP53';

-- 4. Count patients by cancer type
SELECT c.CancerName, COUNT(*) AS PatientCount
FROM Patient p
JOIN CancerType c ON p.CancerTypeID = c.CancerTypeID
GROUP BY c.CancerName;

-- 5. Show prediction history
SELECT p.Name, c.CancerName, pr.PredictionDate
FROM Prediction pr
JOIN Patient p ON pr.PatientID = p.PatientID
JOIN CancerType c ON pr.PredictedCancerTypeID = c.CancerTypeID;