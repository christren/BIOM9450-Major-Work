# Testing — PetStore Management

## Testing approach

I tested each feature using fictional PetStore data and checked database
changes in MySQL Workbench. I tested successful operations, invalid inputs,
empty results and failure handling.

After completing the application, I repeated the main tests using the final
packaged version to check that the files, database and configuration worked
together outside the original development folder.

Environment: Windows, PHP 8.5.11 and MySQL 8.0.46.

## Functional tests

| Feature | Test performed | Result |
| --- | --- | --- |
| Animal entry | Added animals with and without photos and checked the saved records in MySQL. Blank registration values were stored as NULL. | Passed |
| Image uploads | Uploaded supported images within the 2 MB limit. Confirmed unique filenames, saved relative paths and correct image display. | Passed |
| Upload validation | Tried oversized images and unsupported files. Invalid submissions were rejected without creating an animal or saving an image. | Passed |
| Animal search | Tested individual and combined name, category and registration filters. Checked partial-name matches, no results and missing-photo placeholders. | Passed |
| Result sorting | Tested name, price, category and ID ordering. Filters remained selected, and missing names/prices appeared last where applicable. | Passed |
| Matching-sales report | Checked that animal and merchandise categories matched within the same SaleID. Confirmed the iframe displayed distinct customer/sale combinations. | Passed |
| Customer autocomplete | Tested surname suggestions, empty input, no matches and rapid typing. Older responses did not replace newer results. | Passed |
| Customer identification | Selected customers with the same surname and compared their details with MySQL. Each selection retrieved the correct CustomerID. | Passed |
| Animal editing | Changed a fictional animal’s name, breed, category, colour and price. Reopened the record and confirmed the changes persisted without changing its photo or registration. | Passed |
| Edit validation | Tested negative prices, spaces-only required fields and invalid or nonexistent animal IDs. Invalid requests were rejected with clear messages. | Passed |
| Customer registration | Registered a fictional customer and retrieved them through autocomplete. Checked that the generated ID was unique and the phone number retained its leading zero. | Passed |
| Dashboard | Compared animal/customer/sale totals, category counts and the five lowest-stock products with SQL results. Tested manual and automatic refresh. | Passed |

### Matching-sales verification

I checked Sale 18 directly in MySQL. It contained:

- Animal 10: Sherri, category Dog.
- Merchandise item 4: Dog Kennel-Extra Large, category Dog.

This confirmed that the report identified a category match within the same
transaction rather than across separate customer purchases.

## Checkout and transaction tests

### Successful multi-item order

I selected an existing fictional customer and purchased two different
merchandise products in one order.

I checked that:

- One Sale record and the corresponding SaleItem records were created.
- Each product’s stock decreased by the ordered quantity.
- The receipt included the SaleID, customer, quantities and total.
- The total agreed with the database unit prices rounded to cents.
- Refreshing the receipt did not create another sale or reduce stock again.

Result: Passed.

### Insufficient-stock rollback

I selected an available lower-ID product followed by a higher-ID product
with an order quantity above its available stock.

I recorded the sale count, sale-item count and both stock levels before
submitting. After the order failed, I checked that:

- An insufficient-stock message appeared.
- No new Sale or SaleItem records remained.
- Both stock levels were unchanged.
- The earlier item’s stock reduction had been rolled back.

Result: Passed.

## Validation, security and error handling

| Test | Observed result |
| --- | --- |
| Invalid required fields and quantities | Invalid submissions were blocked. Server-side validation also rejected invalid input independently of JavaScript. |
| Modified animal-entry CSRF token | Submission was rejected without saving an animal or image. |
| Refresh after successful submissions | Previously completed inserts and orders were not repeated. |
| Temporary invalid database configuration | Database-dependent pages showed a generic connection error, and API endpoints returned JSON errors. |
| Configuration restored | Search, customer lookup, dashboard and checkout worked again. |

## Presentation and accessibility

I checked the final british royal racing green inspired theme and homepage image at desktop and
narrow-screen widths.

I confirmed that:

- Navigation links and homepage cards opened the correct pages.
- Active-page highlighting and browser-tab titles were correct.
- Forms remained readable and usable on smaller screens.
- Wide tables scrolled within their panels.
- Keyboard focus indicators and the skip link remained visible.
- Customer details stayed hidden until a customer was selected.
- Dashboard chart widths and refresh behaviour still worked.
- The embedded report retained its own layout.

Result: Passed.

## Final installation and package checks

I completed the following checks before packaging:

- Ran PHP syntax checks across the application files.
- Exported the final database structure and data.
- Restored the export into a separate review database.
- Configured and ran a copy of the application from a new folder.
- Repeated animal insertion, search, editing and checkout rollback.
- Checked navigation, uploaded images and relative file paths.
- Included the SQL export, migration, README and configuration template.
- Excluded real config.php, credentials, logs and private backup files.

Result: Passed.

## Testing limitations

These checks were performed manually in my local Windows environment.
They do not establish compatibility with every browser or operating system.

Concurrent checkout from independent browser sessions was not tested.
The application remains a local learning project without production
authentication, authorisation or deployment hardening.