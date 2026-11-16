CREATE TABLE CancerType (
    CancerTypeID INT AUTO_INCREMENT PRIMARY KEY,
    CancerName VARCHAR(100) NOT NULL,
    Description TEXT
);

CREATE TABLE Patient (
    PatientID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Photo VARCHAR(255),
    Diagnosis VARCHAR(255),
    CancerTypeID INT NOT NULL,
    FOREIGN KEY (CancerTypeID)
        REFERENCES CancerType(CancerTypeID)
);

CREATE TABLE AppUser (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE,
    PasswordHash VARCHAR(255),
    Role ENUM('Researcher','Oncologist') NOT NULL
);

CREATE TABLE Professional (
    ProfessionalID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    Specialty VARCHAR(100),
    Institution VARCHAR(100),
    FOREIGN KEY (UserID)
        REFERENCES AppUser(UserID)
);

CREATE TABLE TumorSample (
    SampleID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID INT NOT NULL,
    SampleBarcode VARCHAR(100),
    FOREIGN KEY (PatientID)
        REFERENCES Patient(PatientID)
);

CREATE TABLE Gene (
    GeneID INT AUTO_INCREMENT PRIMARY KEY,
    GeneSymbol VARCHAR(50) NOT NULL UNIQUE,
    GeneDescription VARCHAR(255)
);

CREATE TABLE Variant (
    VariantID INT AUTO_INCREMENT PRIMARY KEY,
    Chromosome VARCHAR(10),
    Position INT,
    ReferenceAllele VARCHAR(255),
    AlternateAllele VARCHAR(255)
);

CREATE TABLE SampleMutation (
    SampleMutationID INT AUTO_INCREMENT PRIMARY KEY,
    SampleID INT NOT NULL,
    GeneID INT NOT NULL,
    VariantID INT NOT NULL,
    MutationType VARCHAR(100),
    PotentialImpact VARCHAR(255),
    FOREIGN KEY (SampleID)
        REFERENCES TumorSample(SampleID),
    FOREIGN KEY (GeneID)
        REFERENCES Gene(GeneID),
    FOREIGN KEY (VariantID)
        REFERENCES Variant(VariantID)
);

CREATE TABLE Prediction (
    PredictionID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID INT NOT NULL,
    ProfessionalID INT NOT NULL,
    PredictedCancerTypeID INT NOT NULL,
    PredictionDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PatientID)
        REFERENCES Patient(PatientID),
    FOREIGN KEY (ProfessionalID)
        REFERENCES Professional(ProfessionalID),
    FOREIGN KEY (PredictedCancerTypeID)
        REFERENCES CancerType(CancerTypeID)
);

CREATE TABLE Feedback (
    FeedbackID INT AUTO_INCREMENT PRIMARY KEY,
    PredictionID INT NOT NULL,
    Comments TEXT,
    AccuracyRating INT,
    FOREIGN KEY (PredictionID)
        REFERENCES Prediction(PredictionID)
);