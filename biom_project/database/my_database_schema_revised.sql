-- BIOM9450 | Exact schema matching the supplied sample_data.sql
-- =====================================================================
-- PURPOSE: Schema documentation / standalone EMPTY-database schema build.
-- The CREATE TABLE / CREATE VIEW statements below are copied verbatim
-- from sample_data.sql (no column names, types, keys or rules changed).
--
-- IMPORTANT:
-- 1. sample_data.sql is already standalone and contains both CREATE
--    statements and INSERT statements. For a new database, import that
--    file ONCE. DO NOT run this file first or afterward in that database.
-- 2. This schema-only file is useful for explaining the design, drawing
--    the ER diagram, or building a separate EMPTY schema-only database.
-- 3. It contains no INSERT, UPDATE, DELETE, DROP or ALTER statements.
-- 4. Select the correct, EMPTY MySQL schema before executing it.
--
-- SNAPSHOT OF SOURCE DUMP:
-- 13 tables + 1 view, 6 cancer types, 300 patients, 300 samples,
-- 11,673 genes, 24,140 variants, 24,513 sample-mutation records,
-- 3 users, 2 professionals. No initial prediction / feedback records.
--
-- NOTES ON NORMALISATION:
-- CancerType 1-to-many Patient; Patient 1-to-many TumorSample;
-- TumorSample/Gene/Variant each 1-to-many SampleMutation;
-- AppUser optional 1-to-1 Professional through UNIQUE Professional.UserID;
-- Professional 1-to-many Patient via Patient.OncologistID.
-- PredictionScore and PredictionMutation are linking tables.
--
-- The Patient.Diagnosis text repeats the cancer label in the supplied
-- demo records; treat it as display text rather than a perfectly
-- normalised clinical attribute. Synthetic demographics are identified
-- using Patient.DemographicsSynthetic = 1.
--
-- sample_data.sql
-- Complete schema and existing data copied from supplied cancer_portal.sql.
-- Source: supplied cancer_portal.sql; not newly collected data.
-- Import ONCE into a NEW EMPTY MySQL database.
-- Standalone file: no other SQL file needs to be imported first.
-- Real TCGA mutations; all display demographics are synthetic.
SET NAMES utf8mb4;
CREATE TABLE `CancerType` (
CancerTypeID INT PRIMARY KEY AUTO_INCREMENT, CancerName VARCHAR(100) NOT NULL UNIQUE, StudyCode VARCHAR(10) NOT NULL UNIQUE, DiagnosisLabel VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `AppUser` (
UserID INT PRIMARY KEY AUTO_INCREMENT, Name VARCHAR(100) NOT NULL, Email VARCHAR(255) NOT NULL UNIQUE, Password VARCHAR(255) NOT NULL COMMENT 'bcrypt hash; use PHP password_verify', Role VARCHAR(50) NOT NULL, CHECK (Role IN ('Patient','Oncologist','Researcher'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `Professional` (
ProfessionalID INT PRIMARY KEY AUTO_INCREMENT, UserID INT NOT NULL UNIQUE, Specialty VARCHAR(100), Institution VARCHAR(100), FOREIGN KEY (UserID) REFERENCES AppUser(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `Patient` (
PatientID INT PRIMARY KEY AUTO_INCREMENT, TCGAPatientID VARCHAR(12) UNIQUE, Name VARCHAR(100) NOT NULL, Photo VARCHAR(255), Age INT, Sex VARCHAR(20), Country VARCHAR(100), Treatment VARCHAR(255), Diagnosis VARCHAR(255), CancerTypeID INT, DemographicsSynthetic BOOLEAN NOT NULL DEFAULT TRUE, UserID INT UNIQUE, OncologistID INT, CHECK (Age IS NULL OR Age BETWEEN 0 AND 120), FOREIGN KEY (CancerTypeID) REFERENCES CancerType(CancerTypeID), FOREIGN KEY (UserID) REFERENCES AppUser(UserID), FOREIGN KEY (OncologistID) REFERENCES Professional(ProfessionalID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `TumorSample` (
SampleID INT PRIMARY KEY AUTO_INCREMENT, PatientID INT NOT NULL, TCGASampleBarcode VARCHAR(100) UNIQUE, CollectionDate DATE, SampleType VARCHAR(100), FOREIGN KEY (PatientID) REFERENCES Patient(PatientID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `Gene` (
GeneID INT PRIMARY KEY AUTO_INCREMENT, GeneSymbol VARCHAR(100) NOT NULL UNIQUE, Description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `Variant` (
VariantID INT PRIMARY KEY AUTO_INCREMENT, GenomeBuild VARCHAR(30) NOT NULL, Chromosome VARCHAR(20) NOT NULL, MutationPosition BIGINT UNSIGNED NOT NULL, EndPosition BIGINT UNSIGNED NOT NULL, ReferenceAllele TEXT NOT NULL, AlternateAllele TEXT NOT NULL, VariantKey CHAR(64) NOT NULL UNIQUE, INDEX idx_location (Chromosome, MutationPosition)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `SampleMutation` (
SampleMutationID INT PRIMARY KEY AUTO_INCREMENT, SampleID INT NOT NULL, GeneID INT NOT NULL, VariantID INT NOT NULL, MutationType VARCHAR(100) NOT NULL, VariantType VARCHAR(20) NOT NULL, Impact VARCHAR(100), ProteinChange VARCHAR(255), SourceRow INT NOT NULL UNIQUE, INDEX idx_gene_sample (GeneID, SampleID), INDEX idx_variant (VariantID), FOREIGN KEY (SampleID) REFERENCES TumorSample(SampleID), FOREIGN KEY (GeneID) REFERENCES Gene(GeneID), FOREIGN KEY (VariantID) REFERENCES Variant(VariantID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `Prediction` (
PredictionID INT PRIMARY KEY AUTO_INCREMENT, PatientID INT NOT NULL, CreatedBy INT NOT NULL, ModelVersion VARCHAR(100) NOT NULL, CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (PatientID) REFERENCES Patient(PatientID), FOREIGN KEY (CreatedBy) REFERENCES AppUser(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `PredictionScore` (
PredictionID INT NOT NULL, CancerTypeID INT NOT NULL, Score DECIMAL(10,9) NOT NULL, PRIMARY KEY (PredictionID,CancerTypeID), CHECK (Score BETWEEN 0 AND 1), FOREIGN KEY (PredictionID) REFERENCES Prediction(PredictionID), FOREIGN KEY (CancerTypeID) REFERENCES CancerType(CancerTypeID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `PredictionMutation` (
PredictionID INT NOT NULL, SampleMutationID INT NOT NULL, PRIMARY KEY (PredictionID,SampleMutationID), FOREIGN KEY (PredictionID) REFERENCES Prediction(PredictionID), FOREIGN KEY (SampleMutationID) REFERENCES SampleMutation(SampleMutationID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `Feedback` (
FeedbackID INT PRIMARY KEY AUTO_INCREMENT, PredictionID INT NOT NULL, UserID INT NOT NULL, IsAccurate BOOLEAN, Comments TEXT, CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, CHECK (IsAccurate IS NULL OR IsAccurate IN (0,1)), FOREIGN KEY (PredictionID) REFERENCES Prediction(PredictionID), FOREIGN KEY (UserID) REFERENCES AppUser(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE TABLE `AuditLog` (
AuditID BIGINT PRIMARY KEY AUTO_INCREMENT, UserID INT, Action VARCHAR(100) NOT NULL, EntityType VARCHAR(100), EntityID VARCHAR(100), CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (UserID) REFERENCES AppUser(UserID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
CREATE VIEW ModelInput AS
SELECT sm.SampleMutationID, p.PatientID, p.TCGAPatientID,
 s.TCGASampleBarcode AS Tumor_Sample_Barcode, g.GeneSymbol AS Hugo_Symbol,
 sm.MutationType AS Variant_Classification, sm.VariantType AS Variant_Type,
 v.ReferenceAllele AS Reference_Allele, v.AlternateAllele AS Tumor_Seq_Allele2,
 v.GenomeBuild AS NCBI_Build, v.Chromosome, v.MutationPosition AS Start_Position,
 v.EndPosition AS End_Position, sm.Impact AS IMPACT, sm.ProteinChange AS HGVSp_Short
FROM SampleMutation sm JOIN TumorSample s ON s.SampleID=sm.SampleID
JOIN Patient p ON p.PatientID=s.PatientID JOIN Gene g ON g.GeneID=sm.GeneID
JOIN Variant v ON v.VariantID=sm.VariantID;
