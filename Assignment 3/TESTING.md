# Manual Test Log — PetStore Management

## Evidence status
The developer reported that feature-by-feature tests passed during the guided build. The assistant reviewed selected repository files but did not run the local website or database. Entries below are a final regression checklist, not independently executed test results.

Final-package regression date: TO COMPLETE
Browser/version: TO COMPLETE
PHP/MySQL versions: TO COMPLETE
Isolated review database/folder: TO COMPLETE

Fill Actual result and Status after testing the final extracted package. Do not replace missing evidence with an assumed pass.

| ID | Test/action | Expected result | Actual result / evidence | Status |
| --- | --- | --- | --- | --- |
| T01 | Add animal without photo | Exactly one row; registration blank becomes NULL | Development pass reported; final result pending | Retest |
| T02 | Add animal with valid image <=2 MiB | Unique images/ file and matching Animal.Photo; image displays | Development pass reported; final result pending | Retest |
| T03 | Oversized image and non-image upload | Rejected; no animal/file saved | Development pass reported; final result pending | Retest |
| T04 | Combine name/category/registration filters | All returned rows satisfy filters | Development pass reported; final result pending | Retest |
| T05 | No-match search, missing photo | Clear empty result; placeholder for missing photo | Development pass reported; final result pending | Retest |
| T06 | Sort by name/price in both directions | Correct order, missing values last, filters preserved | Development pass reported; final result pending | Retest |
| T07 | Matching-sales iframe and SQL verification | Same-sale category match; distinct customer/sale rows | Supplied example: Sale 18, Sherri (Animal 10, Dog), Dog Kennel-Extra Large (Item 4, Dog). Recheck final data | Retest |
| T08 | Autocomplete duplicate surname Cummings | Distinct IDs; selected record matches SQL | Development pass reported; final result pending | Retest |
| T09 | Empty/no-match surname and rapid typing | Clear state; old responses do not overwrite newer state | Development pass reported; final result pending | Retest |
| T10 | Edit fictional animal and reload | Target fields saved; photo/registration preserved; count unchanged | Development pass reported; final result pending | Retest |
| T11 | Negative price, spaces-only required name, invalid ID | Rejected by appropriate browser/server validation | Development pass reported; final result pending | Retest |
| T12 | Register fictional customer with 0400 000 000 | One ID; leading zero preserved; lookup works | Development pass reported; final result pending | Retest |
| T13 | Multi-item checkout with two different products | One sale, two item rows, correct cents total, stock reduced | Development pass reported; enter actual IDs/counts below | Retest |
| T14 | Later ordered item has insufficient stock | No new Sale/SaleItem rows; all earlier stock restored | Development pass reported; enter before/after evidence below | Retest |
| T15 | Refresh receipt and resubmit consumed order token | No duplicate sale from refresh/replayed token | Refresh pass reported; explicit token replay evidence pending | Retest |
| T16 | Dashboard vs SQL, manual/automatic refresh | Counts/category bars/bottom five agree; no full reload | Development pass reported; final result pending | Retest |
| T17 | Tamper with animal-entry CSRF token | Error; no animal or file saved | Development pass reported; final result pending | Retest |
| T18 | Temporary invalid configured database, then restore | Home works; APIs return JSON error/500; features recover after restore | Development pass reported; final result pending | Retest |
| T19 | Keyboard navigation, skip link, narrow viewport | Visible focus; usable controls; internal table scrolling | Development pass reported; record viewport/browser | Retest |
| T20 | Restore final dump into isolated schema and run extracted ZIP | Installation works without original folder/database | Not yet established | Pending |

## Required detailed evidence: successful checkout
Customer ID: TO COMPLETE
Sale ID: TO COMPLETE
Item A ID / quantity / unit price: TO COMPLETE
Item B ID / quantity / unit price: TO COMPLETE
Expected total / actual receipt total: TO COMPLETE
Sale row count before / after: TO COMPLETE
SaleItem row count before / after: TO COMPLETE
Stock A before / after: TO COMPLETE
Stock B before / after: TO COMPLETE
Evidence screenshot/query output: TO COMPLETE

## Required detailed evidence: insufficient-stock rollback
Choose an available lower-ID item, then a higher-ID item requested above its stock.
Sale row count before / after: TO COMPLETE (must be equal)
SaleItem row count before / after: TO COMPLETE (must be equal)
Lower-ID item / quantity / stock before / after: TO COMPLETE (stock must be unchanged)
Higher-ID item / requested quantity / stock before / after: TO COMPLETE (stock must be unchanged)
Displayed error: TO COMPLETE
Evidence screenshot/query output: TO COMPLETE
Note: compare rows and stock, not the next AUTO_INCREMENT number.

## Optional additional edge-case checks
- With JavaScript disabled, PHP still rejects invalid required values and invalid quantities.
- Change a checkout input's data-cents in developer tools: official server prices remain unchanged.
- Open two independent browser sessions and order against the same low-stock item: stock must not become negative. This concurrency test has not been established by earlier reports.
- Use fictional text containing apostrophes and HTML-like characters: data remains text, not executable markup.
- Inspect the final ZIP and Git tracking for real config.php or credential-bearing backups.
- Run PHP syntax checks on all PHP files from the project folder.

## Notes and limitations
Any destructive/failure tests must use fictional records and preferably the isolated review database. Do not import a dump over the working development database. Capture evidence before and after transactional tests. Clean-install verification and final screenshots remain necessary even if earlier development tests passed.
