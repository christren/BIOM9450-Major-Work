# PetStore Management: BIOM9450 Assignment 3
## by Christian Schmooel (z5417031)

## Purpose and scope
A locally-run educational application for managing fictional PetStore animals, customers and merchandise orders. Built with HTML, CSS, JavaScript, PHP and MySQL. No Composer, npm, charting libraries or internet-hosted assets are needed. 

Development environment: Windows, PHP 8.5.11 and MySQL Community Server 8.0.46. Other platforms have not been verified. This application has no user authentication or role-based permissions.

## Requirements
- PHP 8.0 or later with PDO, pdo_mysql and fileinfo enabled; compatibility outside the development environment must be tested.
- MySQL 8.0 or later, using InnoDB tables.
- PHP-enabled local server; VS Code Live Server alone does not execute PHP.
- Modern browser with JavaScript enabled for autocomplete, dashboard and live estimates.
- Writable images/folder and working PHP sessions.

I recommend these local php.ini settings:

```ini
extension=pdo_mysql
extension=fileinfo
file_uploads = On
upload_max_filesize = 4M
post_max_size = 8M
```

Restart PHP after changing its config. The application enforces its own 2 MB image limit. 

## Installation from the final package
1. Extract the complete project to a new folder.
2. Start MySQL.
3. Locate sql/petstore_final.sql.
4. Inspect the dump for CREATE DATABASE and USE statements before importing. Import only into a disposable/new installation or a database you have backed up. The dump may contain DROP TABLE statements and must not be imported over existing data you want to keep. 
5. Use MySQL Workbench Server > Data Import to import the self-contained SQL file. If the dump contains no schema selection, create/select petstore_db as the import target. Verify that Animal, Customer, Sale, SaleAnimal, SaleItem and Merchandise are present.
6. The final export should already contain the applied migration: CustomerID and SaleID use AUTO_INCREMENT, and Customer.Phone is VARCHAR(30). Do not rerun the historical migration when using the final export.
7. Copy config.example.php to config.php. Enter the local MySQL host, port, database, username and password. Keep config.php private and out of the ZIP/repository.
8. Ensure images/ exists, contains the packaged photos and is writable by PHP.
9. Open a terminal in the extracted project folder and run:

```
php -S localhost:3000
```

Alternatively use the VS Code PHP Server extension. Use a different port if 3000 is occupied. Keep the server local; it is for development.
10. Open http://localhost:3000/index.php. Run the checks in TESTING.md.

For a separate review database, inspect a COPY of the dump and adjust schema-selection statements to the review schema before importing. Set only the extracted copy's config.php to that schema. Never overwrite the development database as part of a clean-install test.

## Configuration
config.example.php lists all required keys, config.php returns the same array with real local values. db.php creates the shared PDO connection, uses exceptions and native prepared statements, and returns generic connection errors. APIs receive JSON errors; ordinary pages receive a plain-text message. Detailed diagnostics go to the PHP error log.

## Features and files
| Feature | Main files |
| --- | --- |
| Landing page and quick actions | index.php |
| Animal entry and optional image upload | animal_add.php, includes/upload.php, js/animal_add.js |
| Animal filters, images and sorting | animal_search.php |
| Category-matching sales report in iframe | matching_sales.php, matching_sales_results.php |
| Live customer lookup | customer_lookup.php, customer_api.php, js/customer_lookup.js |
| Animal editing | animal_edit.php, js/animal_edit.js |
| Customer registration | customer_register.php, js/customer_register.js |
| Multi-item checkout and receipt | checkout.php, checkout_receipt.php, js/checkout.js |
| Live dashboard | dashboard.php, dashboard_api.php, js/dashboard.js |
| Shared layout | includes/header.php, includes/footer.php |
| Styles | css/style.css |
| Database connection | db.php, config.example.php and private config.php |
| Historical schema migration | sql/001_customer_sale_setup.sql |
| Final schema and data | sql/petstore_final.sql — add before packaging |
| Manual test evidence | TESTING.md |

## Tables and relationships used
- Animal: animal identity, name, category, breed, registration, colour, list price and optional photo path.
- Customer: identity, names, phone and address fields.
- Sale: sale identity/date and customer/employee references.
- SaleAnimal: links SaleID to AnimalID with an animal selling price.
- Merchandise: item identity, description, category, stock and list price.
- SaleItem: links SaleID to ItemID with quantity and unit selling price.
- Employee: referenced by the supplied Sale schema; this application creates merchandise sales with EmployeeID NULL.
Other supplied tables are preserved in the complete database export.

## Implementation decisions
- AnimalID, CustomerID and SaleID are database-generated; existing IDs were preserved during migration.
- Registered is an organisation code such as AKC/CFA, or NULL when absent; it is NOT a Boolean.
- New customer phone values are text to preserve leading zeroes and formatting. Original zeroes lost under numeric storage cannot be recovered automatically.
- Name search is partial; category and registration status can be combined. Sorting uses a fixed SQL allowlist, with missing names/prices last and ID tie-breakers.
- Uploads accept JPEG, PNG or GIF, validate size/type on the server, generate filenames and store relative images/... paths. Missing/invalid photo paths display a placeholder.
- Customer search uses surname prefixes and returns at most ten suggestions. Full records are retrieved by CustomerID.
- Animal editing updates only name, breed, category, colour and list price. Required text fields must be supplied for legacy unnamed records. Optional blank colour/price is stored as NULL.
- Checkout supports multiple different merchandise items; one or more selected products is valid. Customer registration is a separate completed operation before checkout, so a failed order does not remove a previously registered customer.
- The server obtains official prices from MySQL, rounds unit prices to cents and calculates totals with integer cents. Browser estimates are not trusted.
- Checkout uses an InnoDB transaction, locking reads and guarded stock updates. Failure rolls back the sale, its items and stock updates. AUTO_INCREMENT gaps after rollback are normal.
- SaleItem.SalePrice is the unit price. Multiply by Quantity for a line total.
- CSRF tokens protect write forms. Successful submissions redirect; checkout also consumes a session submission token. This is not a durable cross-session payment/idempotency system.
- The latest receipt snapshot is stored in the browser session. Database sale records persist, but an old session receipt is not a permanent searchable receipt archive.
- Dashboard sales are all Sale rows, not only new checkout sales. It refreshes on load, by button and every 60 seconds while visible. Lowest-stock ties use ItemID.
- Prepared statements protect user-supplied query values; HTML output is escaped and JavaScript inserts customer values with textContent.

## Known limitations
- As it is an assignment, no authentication, authorisation or deployment hardening.
- Supplied money columns remain FLOAT. Integer-cent calculations improve totals but do not make storage exact; production money storage should use an appropriate DECIMAL design.
- Table-name case must be reconciled before deployment to a case-sensitive MySQL environment. Windows was the development target.
- Uploaded files are not antivirus-scanned or re-encoded, and public deployment requires additional upload isolation/hardening.
- No durable order request key or persistent receipt history interface is implemented.
- Large datasets would need pagination and a more scalable customer selector.
- Automated tests, independent browser execution and a completed isolated restore are not established by this document.

## Packaging
Include application files, CSS/JS, images, both documentation files, config.example.php, final SQL export and migration history. Exclude real config.php, .git/, editor/session files, logs and unrelated assignments. Verify the extracted ZIP, not only the development folder.

# This README.md and TESTING.md were generated with the help of Claude Sonnet 5.5, in alignment with the UNSW acceptable AI-use policy. All contents were checked and validated by myself and reflect my intended design purpose; Claude Sonnet 5.5 was also used for learning (via explanations), debugging errors and for writing manual-heavy repetitive code. 