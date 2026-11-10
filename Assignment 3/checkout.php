<?php

session_start();

require __DIR__ . '/db.php';

function checkoutEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function positiveInteger(mixed $value): int|false
{
    if (!is_string($value) && !is_int($value)) {
        return false;
    }

    return filter_var(
        $value,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$customers = [];
$products = [];
$quantities = [];

$customerId = positiveInteger($_GET['customer_id'] ?? '') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = positiveInteger($_POST['customer_id'] ?? '') ?: '';

    $csrf = $_POST['csrf_token'] ?? '';
    $orderToken = $_POST['checkout_token'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['csrf_token'], $csrf)
    ) {
        $errors[] = 'The form security check failed. Reload and try again.';
    }

    if (
        !is_string($orderToken) ||
        !hash_equals($_SESSION['checkout_token'], $orderToken)
    ) {
        $errors[] = 'This order form has expired or was already processed. '
            . 'Reload before placing another order.';
    }

    if ($customerId === '') {
        $errors[] = 'Select a customer.';
    }

    $submitted = $_POST['quantities'] ?? [];

    if (!is_array($submitted) || count($submitted) > 100) {
        $errors[] = 'Invalid merchandise selection.';
    } else {
        foreach ($submitted as $rawItemId => $rawQuantity) {
            $itemId = positiveInteger($rawItemId);

            if (!is_string($rawQuantity)) {
                $errors[] = 'Invalid quantity.';
                continue;
            }

            $quantity = filter_var(
                $rawQuantity,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => 9999]]
            );

            if ($itemId === false || $quantity === false) {
                $errors[] = 'Quantities must be whole numbers from 0 to 9999.';
                continue;
            }

            if ($quantity > 0) {
                $quantities[$itemId] = $quantity;
            }
        }
    }

    if (!$quantities) {
        $errors[] = 'Choose at least one merchandise item.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Keep the selected customer available during this order.
            $statement = $pdo->prepare(
                'SELECT CustomerID, FirstName, LastName
                 FROM Customer
                 WHERE CustomerID = :id
                 FOR SHARE'
            );

            $statement->execute(['id' => $customerId]);
            $customer = $statement->fetch();

            if (!$customer) {
                throw new RuntimeException('The selected customer no longer exists.');
            }

            // Consistent item order reduces conflicting lock sequences.
            ksort($quantities, SORT_NUMERIC);

            $findProduct = $pdo->prepare(
                'SELECT ItemID, Description, QuantityOnHand, ListPrice
                 FROM Merchandise
                 WHERE ItemID = :id
                 FOR UPDATE'
            );

            $createSale = $pdo->prepare(
                'INSERT INTO Sale (SaleDate, EmployeeID, CustomerID)
                 VALUES (CURRENT_DATE(), NULL, :customer_id)'
            );

            $createSale->execute(['customer_id' => $customerId]);
            $saleId = (int) $pdo->lastInsertId();

            $insertItem = $pdo->prepare(
                'INSERT INTO SaleItem (SaleID, ItemID, Quantity, SalePrice)
                 VALUES (:sale_id, :item_id, :quantity, :price)'
            );

            $updateStock = $pdo->prepare(
                'UPDATE Merchandise
                 SET QuantityOnHand = QuantityOnHand - :quantity
                 WHERE ItemID = :item_id
                   AND QuantityOnHand >= :required'
            );

            $receiptItems = [];
            $totalCents = 0;

            foreach ($quantities as $itemId => $quantity) {
                $findProduct->execute(['id' => $itemId]);
                $product = $findProduct->fetch();

                if (!$product) {
                    throw new RuntimeException(
                        'A selected merchandise item no longer exists.'
                    );
                }

                if ((int) $product['QuantityOnHand'] < $quantity) {
                    throw new RuntimeException(
                        'Insufficient stock for ' . $product['Description']
                        . '. Available: ' . $product['QuantityOnHand'] . '.'
                    );
                }

                $databasePrice = (float) $product['ListPrice'];

                if (
                    !is_finite($databasePrice) ||
                    $databasePrice < 0 ||
                    $databasePrice > 999999999.99
                ) {
                    throw new RuntimeException(
                        'A selected item has an invalid price. Order cancelled.'
                    );
                }

                // Round each database unit price to cents before calculating.
                $unitCents = (int) round($databasePrice * 100);
                $lineCents = $unitCents * $quantity;
                $totalCents += $lineCents;

                $insertItem->execute([
                    'sale_id' => $saleId,
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                    'price' => number_format($unitCents / 100, 2, '.', ''),
                ]);

                $updateStock->execute([
                    'quantity' => $quantity,
                    'item_id' => $itemId,
                    'required' => $quantity,
                ]);

                if ($updateStock->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Stock changed during checkout. Please try again.'
                    );
                }

                $receiptItems[] = [
                    'id' => $itemId,
                    'description' => $product['Description'],
                    'quantity' => $quantity,
                    'unit_cents' => $unitCents,
                    'line_cents' => $lineCents,
                ];
            }

            $pdo->commit();

            $_SESSION['last_receipt'] = [
                'sale_id' => $saleId,
                'customer_id' => (int) $customerId,
                'customer_name' => trim(
                    ($customer['FirstName'] ?? '') . ' '
                    . ($customer['LastName'] ?? '')
                ),
                'items' => $receiptItems,
                'total_cents' => $totalCents,
            ];

            // A successful order consumes this form's submission token.
            unset($_SESSION['checkout_token']);

            header('Location: checkout_receipt.php', true, 303);
            exit;

        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($error->getMessage());

            $errors[] = $error instanceof RuntimeException
                && !($error instanceof PDOException)
                ? $error->getMessage()
                : 'Checkout failed. No order changes were saved. Please try again.';
        }
    }
}

$pageReady = true;

try {
    $customers = $pdo->query(
        'SELECT CustomerID, FirstName, LastName
         FROM Customer
         ORDER BY LastName, FirstName, CustomerID'
    )->fetchAll();

    $products = $pdo->query(
        'SELECT ItemID, Description, QuantityOnHand, ListPrice
         FROM Merchandise
         ORDER BY ItemID'
    )->fetchAll();

} catch (PDOException $error) {
    error_log($error->getMessage());
    $pageReady = false;
    $errors[] = 'Checkout information could not be loaded.';
}

require __DIR__ . '/includes/header.php';

?>

<section class="card">
    <h2>Merchandise checkout</h2>

    <p>
        Select a customer and enter quantities.
        Leave unwanted items at zero.
    </p>

    <?php if ($errors): ?>
        <div class="notice error" role="alert">
            <p>The order was not completed:</p>
            <ul>
                <?php foreach (array_unique($errors) as $error): ?>
                    <li><?= checkoutEscape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($pageReady): ?>
        <form id="checkout-form" method="post" action="checkout.php">
            <input type="hidden" name="csrf_token"
                   value="<?= checkoutEscape($_SESSION['csrf_token']) ?>">

            <input type="hidden" name="checkout_token"
                   value="<?= checkoutEscape($_SESSION['checkout_token']) ?>">

            <div class="field">
                <label for="customer_id">Customer (required)</label>

                <select id="customer_id" name="customer_id" required>
                    <option value="">Select a customer</option>

                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= (int) $customer['CustomerID'] ?>"
                            <?= (int) $customerId === (int) $customer['CustomerID']
                                ? 'selected' : '' ?>>
                            <?= checkoutEscape(
                                trim(
                                    ($customer['FirstName'] ?? '') . ' '
                                    . ($customer['LastName'] ?? '')
                                ) . ' — ID ' . $customer['CustomerID']
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <p>
                New customer?
                <a href="customer_register.php">Register before ordering</a>.
            </p>

            <div class="table-wrapper" tabindex="0"
                 role="region" aria-label="Merchandise selection">
                <table>
                    <caption>Merchandise available for ordering</caption>
                    <thead>
                        <tr>
                            <th scope="col">Item</th>
                            <th scope="col">Unit price</th>
                            <th scope="col">Stock</th>
                            <th scope="col">Quantity</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $itemId = (int) $product['ItemID'];
                            $cents = (int) round(
                                (float) $product['ListPrice'] * 100
                            );
                            ?>

                            <tr>
                                <td><?= checkoutEscape(
                                    (string) $product['Description']
                                ) ?></td>

                                <td class="price">
                                    $<?= number_format($cents / 100, 2) ?>
                                </td>

                                <td><?= (int) $product['QuantityOnHand'] ?></td>

                                <td>
                                    <input class="checkout-quantity"
                                           type="number"
                                           name="quantities[<?= $itemId ?>]"
                                           min="0" max="9999" step="1"
                                           data-cents="<?= $cents ?>"
                                           aria-label="<?= checkoutEscape(
                                               'Quantity for ' .
                                               $product['Description']
                                           ) ?>"
                                           value="<?= $quantities[$itemId] ?? 0 ?>"
                                           required>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="checkout-summary">
                <p>
                    Estimated total:
                    <output id="checkout-total" aria-live="polite">$0.00</output>
                </p>
                <small>
                    The final total and stock availability are checked
                    again on the server.
                </small>
            </div>

            <button type="submit">Place order</button>
        </form>
    <?php endif; ?>
</section>

<script src="js/checkout.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>