INSERT INTO CancerType (CancerName, Description)
VALUES
('Breast Cancer', 'Cancer that develops in breast tissue'),
('Lung Cancer', 'Cancer that develops in the lungs'),
('Prostate Cancer', 'Cancer affecting the prostate gland');

INSERT INTO Patient (Name, Photo, Diagnosis, CancerTypeID)
VALUES
('John Smith', 'john.jpg', 'Breast Cancer', 1),
('Sarah Lee', 'sarah.jpg', 'Lung Cancer', 2),
('Michael Brown', 'michael.jpg', 'Prostate Cancer', 3);

INSERT INTO AppUser (Name, Email, PasswordHash, Role)
VALUES
('Dr Wilson', 'wilson@hospital.com', 'hashedpassword1', 'Oncologist'),
('Dr Chen', 'chen@research.org', 'hashedpassword2', 'Researcher');

INSERT INTO Professional (UserID, Specialty, Institution)
VALUES
(1, 'Medical Oncology', 'Royal Prince Alfred Hospital'),
(2, 'Cancer Genomics', 'University Research Centre');

INSERT INTO TumorSample (PatientID, SampleBarcode)
VALUES
(1, 'SAMPLE001'),
(2, 'SAMPLE002'),
(3, 'SAMPLE003');

INSERT INTO Gene (GeneSymbol, GeneDescription)
VALUES
('TP53', 'Tumour suppressor gene'),
('BRCA1', 'Breast cancer susceptibility gene'),
('KRAS', 'Cell signalling gene');

INSERT INTO Variant (Chromosome, Position, ReferenceAllele, AlternateAllele)
VALUES
('17', 7579472, 'C', 'T'),
('17', 43044295, 'A', 'G'),
('12', 25398284, 'G', 'A');

INSERT INTO SampleMutation
(SampleID, GeneID, VariantID, MutationType, PotentialImpact)
VALUES
(1, 1, 1, 'Missense Mutation', 'High'),
(2, 2, 2, 'Nonsense Mutation', 'High'),
(3, 3, 3, 'Silent Mutation', 'Low');

INSERT INTO Prediction
(PatientID, ProfessionalID, PredictedCancerTypeID)
VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 3);

INSERT INTO Feedback
(PredictionID, Comments, AccuracyRating)
VALUES
(1, 'Prediction matched diagnosis', 5),
(2, 'Reasonably accurate', 4),
(3, 'Requires further review', 3);