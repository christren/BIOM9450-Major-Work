USE petstore_db;

-- Run locally after backing up the database
-- Existing ID values and foreign key relationships are preserved
-- Do not insert/update/delete records during this migration

SET @previous_fk_checks = @@SESSION.foreign_key_checks;
SET SESSION foreign_key_checks = 0;

ALTER TABLE Customer
    MODIFY COLUMN CustomerID INT NOT NULL AUTO_INCREMENT,
    MODIFY COLUMN Phone VARCHAR(30) NULL DEFAULT NULL;

ALTER TABLE Sale
    MODIFY COLUMN SaleID INT NOT NULL AUTO_INCREMENT;

SET SESSION foreign_key_checks = @previous_fk_checks;

SHOW COLUMNS FROM Customer;
SHOW COLUMNS FROM Sale;

SELECT @@SESSION.foreign_key_checks AS ForeignKeyChecks;