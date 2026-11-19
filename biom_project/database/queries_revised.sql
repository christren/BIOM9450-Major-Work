-- BIOM9450: READ-ONLY QUERIES AND DATABASE VALIDATION
-- ================================================================
-- Designed for the ACTUAL schema inside sample_data.sql.
-- Import sample_data.sql ONE TIME into a NEW EMPTY schema (if not yet
-- imported), then select that schema as the default in MySQL Workbench.
--
-- These statements do not change the existing data.
-- Run SELECT and SHOW statements individually or the entire script.
-- Expected counts are from a STATIC audit of the supplied SQL file.
-- Confirm the results against your actual MySQL installation.
--
-- NOTE: Patient display demographics are synthetic in this dataset.
-- They are NOT verified TCGA clinical demographics.
-- ==============================================================

-- 01. Confirm the active schema; if NULL, select your schema first.
SELECT DATABASE() AS CurrentDatabase;
SHOW FULL TABLES;

-- 02. Record counts from the current MySQL database.
-- Expected: 6, 3, 2, 300, 300, 11673, 24140, 24513.
-- Prediction/PredictionScore/PredictionMutation/Feedback/AuditLog
-- initially contain zero records in sample_data.sql.
SELECT 'CancerType' AS TableName, COUNT(*) AS RecordCount FROM CancerType
UNION ALL SELECT 'AppUser', COUNT(*) FROM AppUser
UNION ALL SELECT 'Professional', COUNT(*) FROM Professional
UNION ALL SELECT 'Patient', COUNT(*) FROM Patient
UNION ALL SELECT 'TumorSample', COUNT(*) FROM TumorSample
UNION ALL SELECT 'Gene', COUNT(*) FROM Gene
UNION ALL SELECT 'Variant', COUNT(*) FROM Variant
UNION ALL SELECT 'SampleMutation', COUNT(*) FROM SampleMutation
UNION ALL SELECT 'Prediction', COUNT(*) FROM Prediction
UNION ALL SELECT 'PredictionScore', COUNT(*) FROM PredictionScore
UNION ALL SELECT 'PredictionMutation', COUNT(*) FROM PredictionMutation
UNION ALL SELECT 'Feedback', COUNT(*) FROM Feedback
UNION ALL SELECT 'AuditLog', COUNT(*) FROM AuditLog;

-- 03. Show six cancer groups, patient counts, sample counts,
--     and mutation-record counts WITHOUT counting patients repeatedly.
SELECT
    c.CancerName,
    c.StudyCode,
    c.DiagnosisLabel,
    COUNT(DISTINCT p.PatientID) AS Patients,
    COUNT(DISTINCT s.SampleID) AS TumorSamples,
    COUNT(sm.SampleMutationID) AS MutationRecords
FROM CancerType AS c
LEFT JOIN Patient AS p ON p.CancerTypeID = c.CancerTypeID
LEFT JOIN TumorSample AS s ON s.PatientID = p.PatientID
LEFT JOIN SampleMutation AS sm ON sm.SampleID = s.SampleID
GROUP BY c.CancerTypeID, c.CancerName, c.StudyCode, c.DiagnosisLabel
ORDER BY c.CancerTypeID;

-- 04. Patient details and associated cancer. All display demographics
--     in the dump have DemographicsSynthetic=1.
SELECT
    p.PatientID,
    p.TCGAPatientID,
    p.Name AS DisplayName,
    p.Age,
    p.Sex,
    p.Country,
    p.DemographicsSynthetic,
    p.Diagnosis AS DisplayDiagnosis,
    c.CancerName,
    c.StudyCode
FROM Patient AS p
LEFT JOIN CancerType AS c ON c.CancerTypeID = p.CancerTypeID
ORDER BY p.PatientID
LIMIT 20;

-- 05. Oncologist and researcher accounts / specialties.
--     Password hashes are intentionally NOT selected.
SELECT
    pro.ProfessionalID,
    u.UserID,
    u.Name,
    u.Email,
    u.Role,
    pro.Specialty,
    pro.Institution
FROM Professional AS pro
JOIN AppUser AS u ON u.UserID = pro.UserID
ORDER BY pro.ProfessionalID;

-- 06. Demonstrate links across patient -> sample -> mutation
--     -> gene and variant.
SELECT
    p.TCGAPatientID,
    s.TCGASampleBarcode,
    g.GeneSymbol,
    sm.MutationType,
    sm.VariantType,
    sm.Impact,
    sm.ProteinChange,
    v.GenomeBuild,
    v.Chromosome,
    v.MutationPosition,
    v.EndPosition,
    v.ReferenceAllele,
    v.AlternateAllele
FROM SampleMutation AS sm
JOIN TumorSample AS s ON s.SampleID = sm.SampleID
JOIN Patient AS p ON p.PatientID = s.PatientID
JOIN Gene AS g ON g.GeneID = sm.GeneID
JOIN Variant AS v ON v.VariantID = sm.VariantID
ORDER BY sm.SampleMutationID
LIMIT 25;

-- 07. Find distinct patients with TP53 mutations (search by gene).
SELECT DISTINCT
    p.TCGAPatientID,
    p.Name AS DisplayName,
    c.StudyCode,
    s.TCGASampleBarcode
FROM Gene AS g
JOIN SampleMutation AS sm ON sm.GeneID = g.GeneID
JOIN TumorSample AS s ON s.SampleID = sm.SampleID
JOIN Patient AS p ON p.PatientID = s.PatientID
LEFT JOIN CancerType AS c ON c.CancerTypeID = p.CancerTypeID
WHERE g.GeneSymbol = 'TP53'
ORDER BY p.TCGAPatientID;

-- 08. Filter variants by chromosome and genomic coordinate range.
--     Values are examples of filter parameters. Adjust as needed.
SELECT
    v.Chromosome,
    v.MutationPosition,
    v.EndPosition,
    g.GeneSymbol,
    sm.MutationType,
    s.TCGASampleBarcode
FROM Variant AS v
JOIN SampleMutation AS sm ON sm.VariantID = v.VariantID
JOIN Gene AS g ON g.GeneID = sm.GeneID
JOIN TumorSample AS s ON s.SampleID = sm.SampleID
WHERE v.Chromosome = '17'
  AND v.MutationPosition BETWEEN 7000000 AND 8000000
ORDER BY v.MutationPosition, s.TCGASampleBarcode
LIMIT 50;

-- 09. Most frequent mutation classifications.
SELECT
    sm.MutationType,
    COUNT(*) AS MutationRecords,
    COUNT(DISTINCT sm.SampleID) AS SamplesAffected
FROM SampleMutation AS sm
GROUP BY sm.MutationType
ORDER BY MutationRecords DESC;

-- 10. Frequently mutated genes, including number of distinct
--     affected tumour samples (different from row count).
SELECT
    g.GeneSymbol,
    COUNT(*) AS MutationRecords,
    COUNT(DISTINCT sm.SampleID) AS SamplesAffected
FROM Gene AS g
JOIN SampleMutation AS sm ON sm.GeneID = g.GeneID
GROUP BY g.GeneID, g.GeneSymbol
ORDER BY SamplesAffected DESC, MutationRecords DESC
LIMIT 25;

-- 11. Check all core foreign-key relationships.
--     Every OrphanCount should equal ZERO.
SELECT 'Patient -> CancerType' AS Relationship, COUNT(*) AS OrphanCount
FROM Patient p LEFT JOIN CancerType c ON c.CancerTypeID=p.CancerTypeID
WHERE p.CancerTypeID IS NOT NULL AND c.CancerTypeID IS NULL
UNION ALL
SELECT 'Patient -> AppUser', COUNT(*)
FROM Patient p LEFT JOIN AppUser u ON u.UserID=p.UserID
WHERE p.UserID IS NOT NULL AND u.UserID IS NULL
UNION ALL
SELECT 'Patient -> Professional', COUNT(*)
FROM Patient p LEFT JOIN Professional pr ON pr.ProfessionalID=p.OncologistID
WHERE p.OncologistID IS NOT NULL AND pr.ProfessionalID IS NULL
UNION ALL
SELECT 'Professional -> AppUser', COUNT(*)
FROM Professional pr LEFT JOIN AppUser u ON u.UserID=pr.UserID
WHERE u.UserID IS NULL
UNION ALL
SELECT 'TumorSample -> Patient', COUNT(*)
FROM TumorSample s LEFT JOIN Patient p ON p.PatientID=s.PatientID
WHERE p.PatientID IS NULL
UNION ALL
SELECT 'SampleMutation -> TumorSample', COUNT(*)
FROM SampleMutation sm LEFT JOIN TumorSample s ON s.SampleID=sm.SampleID
WHERE s.SampleID IS NULL
UNION ALL
SELECT 'SampleMutation -> Gene', COUNT(*)
FROM SampleMutation sm LEFT JOIN Gene g ON g.GeneID=sm.GeneID
WHERE g.GeneID IS NULL
UNION ALL
SELECT 'SampleMutation -> Variant', COUNT(*)
FROM SampleMutation sm LEFT JOIN Variant v ON v.VariantID=sm.VariantID
WHERE v.VariantID IS NULL;

-- 12. Confirm uniqueness of identifiers in existing data.
--     TotalRows should equal DistinctIDs for each listed field.
SELECT 'Patient.TCGAPatientID' AS Identifier,
       COUNT(*) AS TotalRows,
       COUNT(DISTINCT TCGAPatientID) AS DistinctIDs
FROM Patient
UNION ALL
SELECT 'TumorSample.TCGASampleBarcode', COUNT(*),
       COUNT(DISTINCT TCGASampleBarcode) FROM TumorSample
UNION ALL
SELECT 'Gene.GeneSymbol', COUNT(*), COUNT(DISTINCT GeneSymbol) FROM Gene
UNION ALL
SELECT 'Variant.VariantKey', COUNT(*), COUNT(DISTINCT VariantKey) FROM Variant
UNION ALL
SELECT 'SampleMutation.SourceRow', COUNT(*), COUNT(DISTINCT SourceRow)
FROM SampleMutation;

-- 13. Confirm age range check and synthetic data label.
SELECT
    COUNT(*) AS PatientTotal,
    SUM(CASE WHEN DemographicsSynthetic = 1 THEN 1 ELSE 0 END)
        AS SyntheticDemographics,
    SUM(CASE WHEN Age IS NOT NULL AND (Age < 0 OR Age > 120)
             THEN 1 ELSE 0 END) AS InvalidAgeCount,
    SUM(CASE WHEN CancerTypeID IS NULL THEN 1 ELSE 0 END)
        AS PatientsWithoutCancerType
FROM Patient;

-- 14. Show foreign key definitions actually installed on the server.
SELECT
    k.TABLE_NAME,
    k.COLUMN_NAME,
    k.REFERENCED_TABLE_NAME,
    k.REFERENCED_COLUMN_NAME,
    k.CONSTRAINT_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS k
WHERE k.TABLE_SCHEMA = DATABASE()
  AND k.REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION;

-- 15. Show primary, unique, foreign key, and check constraints.
SELECT
    tc.TABLE_NAME,
    tc.CONSTRAINT_NAME,
    tc.CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS tc
WHERE tc.TABLE_SCHEMA = DATABASE()
ORDER BY tc.TABLE_NAME, tc.CONSTRAINT_TYPE, tc.CONSTRAINT_NAME;

-- 16. Show data types, nullability, defaults and auto-increment.
SELECT
    c.TABLE_NAME,
    c.COLUMN_NAME,
    c.COLUMN_TYPE,
    c.IS_NULLABLE,
    c.COLUMN_DEFAULT,
    c.EXTRA
FROM INFORMATION_SCHEMA.COLUMNS AS c
WHERE c.TABLE_SCHEMA = DATABASE()
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION;

-- 17. Demonstrate many samples can share a variant; the variant
--     definition lives in Variant, observations in SampleMutation.
SELECT
    v.VariantID,
    v.GenomeBuild,
    v.Chromosome,
    v.MutationPosition,
    COUNT(DISTINCT sm.SampleID) AS AffectedSamples
FROM Variant AS v
JOIN SampleMutation AS sm ON sm.VariantID = v.VariantID
GROUP BY v.VariantID, v.GenomeBuild, v.Chromosome, v.MutationPosition
HAVING COUNT(DISTINCT sm.SampleID) > 1
ORDER BY AffectedSamples DESC
LIMIT 20;

-- 18. Demonstrate that gene names are not repeated in the Gene table.
--     Multiple SampleMutation records reference each Gene.GeneID.
SELECT
    g.GeneID,
    g.GeneSymbol,
    COUNT(sm.SampleMutationID) AS AssociatedMutationRecords
FROM Gene AS g
LEFT JOIN SampleMutation AS sm ON sm.GeneID=g.GeneID
GROUP BY g.GeneID, g.GeneSymbol
ORDER BY AssociatedMutationRecords DESC
LIMIT 20;

-- 19. Check that Professional records belong to the expected roles.
--     RoleMismatchCount is expected to be 0 in your initial demo data.
SELECT
    COUNT(*) AS RoleMismatchCount
FROM Professional AS pr
JOIN AppUser AS u ON u.UserID = pr.UserID
WHERE u.Role NOT IN ('Oncologist','Researcher');

-- 20. Existing view for machine-learning integration (read-only).
SELECT * FROM ModelInput LIMIT 10;

-- 21. Show actual schema definitions for presentation and screenshots.
SHOW CREATE TABLE Patient;
SHOW CREATE TABLE Professional;
SHOW CREATE TABLE Variant;
SHOW CREATE TABLE SampleMutation;
SHOW CREATE TABLE PredictionScore;
SHOW CREATE VIEW ModelInput;

-- 22. Prediction / feedback / audit tables are initially empty in
--     sample_data.sql. Their presence proves schema readiness, not
--     that prediction or audit logging has been implemented in PHP.
SELECT
    (SELECT COUNT(*) FROM Prediction) AS Predictions,
    (SELECT COUNT(*) FROM PredictionScore) AS PredictionScores,
    (SELECT COUNT(*) FROM PredictionMutation) AS PredictionMutationLinks,
    (SELECT COUNT(*) FROM Feedback) AS FeedbackRecords,
    (SELECT COUNT(*) FROM AuditLog) AS AuditRecords;
