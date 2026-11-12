-- ============================================
-- Cancer Type
-- ============================================

CREATE TABLE CancerType (
    CancerTypeID INT PRIMARY KEY,
    CancerName VARCHAR(100) NOT NULL
);

-- ============================================
-- Patient
-- One cancer type -> many patients
-- ============================================

CREATE TABLE Patient (
    PatientID INT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Photo VARCHAR(255),
    Age INT,
    Sex VARCHAR(20),
    Diagnosis VARCHAR(255),
    CancerTypeID INT,
    FOREIGN KEY (CancerTypeID) REFERENCES CancerType(CancerTypeID)
);

-- ============================================
-- Tumor Sample
-- One patient -> many samples
-- ============================================

CREATE TABLE TumorSample (
    SampleID INT PRIMARY KEY,
    PatientID INT NOT NULL,
    CollectionDate DATE,
    SampleType VARCHAR(100),
    FOREIGN KEY (PatientID) REFERENCES Patient(PatientID)
);

-- ============================================
-- Gene
-- Stores unique genes
-- ============================================

CREATE TABLE Gene (
    GeneID INT PRIMARY KEY,
    GeneSymbol VARCHAR(50) NOT NULL,
    Description VARCHAR(255)
);

-- ============================================
-- Variant
-- Stores unique mutation variants
-- ============================================

CREATE TABLE Variant (
    VariantID INT PRIMARY KEY,
    Chromosome VARCHAR(20),
    MutationPosition VARCHAR(50),
    MutationType VARCHAR(100),
    Impact VARCHAR(100)
);

-- ============================================
-- Sample Mutation
-- Links samples, genes and variants
-- ============================================

CREATE TABLE SampleMutation (
    SampleMutationID INT PRIMARY KEY,
    SampleID INT NOT NULL,
    GeneID INT NOT NULL,
    VariantID INT NOT NULL,
    FOREIGN KEY (SampleID) REFERENCES TumorSample(SampleID),
    FOREIGN KEY (GeneID) REFERENCES Gene(GeneID),
    FOREIGN KEY (VariantID) REFERENCES Variant(VariantID)
);

-- ============================================
-- Application Users
-- ============================================

CREATE TABLE AppUser (
    UserID INT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(255) UNIQUE,
    Password VARCHAR(255),
    Role VARCHAR(50)
);

-- ============================================
-- Professional
-- Researcher / Oncologist details
-- ============================================

CREATE TABLE Professional (
    ProfessionalID INT PRIMARY KEY,
    UserID INT NOT NULL,
    Specialty VARCHAR(100),
    Institution VARCHAR(100),
    FOREIGN KEY (UserID) REFERENCES AppUser(UserID)
);