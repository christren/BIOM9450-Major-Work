-- Expected: six rows, each PatientCount=50.
SELECT c.CancerName, COUNT(*) AS PatientCount FROM Patient p
JOIN CancerType c ON c.CancerTypeID=p.CancerTypeID GROUP BY c.CancerTypeID,c.CancerName;
-- Patient 1 mutation profile. Use a prepared parameter instead of 1 in PHP.
SELECT * FROM ModelInput WHERE PatientID=1 ORDER BY SampleMutationID;
-- Patients with TP53 mutations.
SELECT DISTINCT p.PatientID,p.Name,p.TCGAPatientID,c.CancerName
FROM Patient p JOIN CancerType c ON c.CancerTypeID=p.CancerTypeID
JOIN TumorSample s ON s.PatientID=p.PatientID
JOIN SampleMutation sm ON sm.SampleID=s.SampleID JOIN Gene g ON g.GeneID=sm.GeneID
WHERE g.GeneSymbol='TP53';
-- Occurrence counts for a chart (not counts of unique variants).
SELECT g.GeneSymbol,COUNT(*) AS MutationRows,COUNT(DISTINCT s.PatientID) AS Patients
FROM SampleMutation sm JOIN Gene g ON g.GeneID=sm.GeneID
JOIN TumorSample s ON s.SampleID=sm.SampleID
GROUP BY g.GeneID,g.GeneSymbol ORDER BY MutationRows DESC LIMIT 20;
-- Expected zero: orphan mutation links.
SELECT COUNT(*) AS Orphans FROM SampleMutation sm
LEFT JOIN TumorSample s ON s.SampleID=sm.SampleID
LEFT JOIN Gene g ON g.GeneID=sm.GeneID LEFT JOIN Variant v ON v.VariantID=sm.VariantID
WHERE s.SampleID IS NULL OR g.GeneID IS NULL OR v.VariantID IS NULL;
