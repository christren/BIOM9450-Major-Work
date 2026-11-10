<?php

session_start();

require __DIR__ . '/db.php';

function registrationEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$fields = [
    'first_name' => ['label' => 'First name', 'max' => 255, 'required' => true],
    'last_name' => ['label' => 'Family name', 'max' => 255, 'required' => true],
    'phone' => ['label' => 'Phone', 'max' => 30, 'required' => false],
    'address' => ['label' => 'Address', 'max' => 255, 'required' => false],
    'zip_code' => ['label' => 'Postcode / ZIP', 'max' => 50, 'required' => false],
];

$values = array_fill_keys(array_keys($fields), '');
$errors = [];

$savedId = $_SESSION['customer_registered_id'] ?? null;
unset($_SESSION['customer_registered_id']);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $errors[] = 'The form security check failed. Reload and try again.';
    }

    foreach ($fields as $key => $field) {
        $input = $_POST[$key] ?? '';

        if (!is_string($input)) {
            $errors[] = 'Invalid ' . strtolower($field['label']) . '.';
            continue;
        }

        $values[$key] = trim($input);

        if ($field['required'] && $values[$key] === '') {
            $errors[] = $field['label'] . ' is required.';
        }

        if (strlen($values[$key]) > $field['max']) {
            $errors[] = $field['label'] . ' is too long.';
        }
    }

    if ($values['phone'] !== '') {
        $digits = preg_replace('/\D/', '', $values['phone']);

        if (
            !preg_match('/\A[+0-9(). -]+\z/', $values['phone']) ||
            strlen($digits) < 7 ||
            strlen($digits) > 15
        ) {
            $errors[] = 'Phone must contain 7–15 digits. '
                . 'Spaces, +, parentheses, dots and hyphens are allowed.';
        }
    }

    if (!$errors) {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO Customer
                    (FirstName, LastName, Phone, Address, ZipCode)
                 VALUES
                    (:first_name, :last_name, :phone, :address, :zip_code)'
            );

            $statement->execute([
                'first_name' => $values['first_name'],
                'last_name' => $values['last_name'],
                'phone' => $values['phone'] === '' ? null : $values['phone'],
                'address' => $values['address'] === '' ? null : $values['address'],
                'zip_code' => $values['zip_code'] === '' ? null : $values['zip_code'],
            ]);

            $_SESSION['customer_registered_id'] = (int) $pdo->lastInsertId();

            header('Location: customer_register.php', true, 303);
            exit;

        } catch (PDOException $error) {
            error_log($error->getMessage());
            $errors[] = 'The customer could not be registered. Please try again.';
        }
    }
}

require __DIR__ . '/includes/header.php';

?>

<section class="card">
    <h2>Register a customer</h2>
    <p>Use fictional information only. First and family names are required.</p>

    <?php if ($savedId !== null): ?>
        <div class="notice success" role="status">
            Customer registered successfully.
            Customer ID: <?= (int) $savedId ?>.
            <a href="checkout.php?customer_id=<?= (int) $savedId ?>">
                Start an order for this customer
            </a>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="notice error" role="alert">
            <p>Please check the following:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= registrationEscape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="customer-register-form" method="post"
          action="customer_register.php">

        <input type="hidden" name="csrf_token"
               value="<?= registrationEscape($_SESSION['csrf_token']) ?>">

        <div class="form-grid">
            <?php foreach ($fields as $key => $field): ?>
                <div class="field">
                    <label for="<?= registrationEscape($key) ?>">
                        <?= registrationEscape($field['label']) ?>
                        <?= $field['required'] ? '(required)' : '(optional)' ?>
                    </label>

                    <input id="<?= registrationEscape($key) ?>"
                           name="<?= registrationEscape($key) ?>"
                           type="<?= $key === 'phone' ? 'tel' : 'text' ?>"
                           maxlength="<?= (int) $field['max'] ?>"
                           <?= $field['required'] ? 'required' : '' ?>
                           value="<?= registrationEscape($values[$key]) ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button type="submit">Register customer</button>
            <a class="button-secondary" href="customer_lookup.php">
                Find an existing customer
            </a>
        </div>
    </form>
</section>

<script src="js/customer_register.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>