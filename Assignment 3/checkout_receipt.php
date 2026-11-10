<?php

session_start();

$receipt = $_SESSION['last_receipt'] ?? null;

function receiptEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

require __DIR__ . '/includes/header.php';

?>

<section class="card">
    <h2>Order receipt</h2>

    <?php if (!$receipt): ?>
        <p>No receipt is available in this browser session.</p>

    <?php else: ?>
        <div class="notice success" role="status">
            Order completed successfully.
        </div>

        <p>Sale ID: <?= (int) $receipt['sale_id'] ?></p>

        <p>
            Customer:
            <?= receiptEscape($receipt['customer_name']) ?>
            — ID <?= (int) $receipt['customer_id'] ?>
        </p>

        <div class="table-wrapper" tabindex="0"
             role="region" aria-label="Order receipt items">
            <table>
                <caption>Purchased merchandise</caption>
                <thead>
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Quantity</th>
                        <th scope="col">Unit price</th>
                        <th scope="col">Line total</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($receipt['items'] as $item): ?>
                        <tr>
                            <td><?= receiptEscape(
                                (string) $item['description']
                            ) ?></td>

                            <td><?= (int) $item['quantity'] ?></td>

                            <td class="price">
                                $<?= number_format($item['unit_cents'] / 100, 2) ?>
                            </td>

                            <td class="price">
                                $<?= number_format($item['line_cents'] / 100, 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="receipt-total">
            Total: $<?= number_format($receipt['total_cents'] / 100, 2) ?>
        </p>
    <?php endif; ?>

    <a class="button-secondary" href="checkout.php">Start another order</a>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>