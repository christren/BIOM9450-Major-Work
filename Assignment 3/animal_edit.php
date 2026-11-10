<?php

session_start();

require __DIR__ . '/db.php';

function editEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$rawId = $_GET['id'] ?? '';

$id = is_string($rawId)
    ? filter_var(
        $rawId,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    )
    : false;

$fields = [
    'name' => [
        'label' => 'Animal name',
        'column' => 'Name',
        'max' => 50,
        'required' => true,
    ],
    'breed' => [
        'label' => 'Breed',
        'column' => 'Breed',
        'max' => 50,
        'required' => true,
    ],
    'category' => [
        'label' => 'Category',
        'column' => 'Category',
        'max' => 50,
        'required' => true,
    ],
    'color' => [
        'label' => 'Colour',
        'column' => 'Color',
        'max' => 30,
        'required' => false,
    ],
];

$values = [
    'name' => '',
    'breed' => '',
    'category' => '',
    'color' => '',
    'price' => '',
];

$errors = [];
$animal = false;
$saved = false;

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($id === false) {
    http_response_code(400);
    $errors[] = 'Invalid animal ID. Select an animal from the search page.';
} else {
    try {
        $statement = $pdo->prepare(
            'SELECT AnimalID, Name, Breed, Category, Color, ListPrice
             FROM Animal
             WHERE AnimalID = :id'
        );

        $statement->execute(['id' => $id]);
        $animal = $statement->fetch();

        if (!$animal) {
            http_response_code(404);
            $errors[] = 'That animal could not be found.';
        }
    } catch (PDOException $error) {
        error_log($error->getMessage());
        http_response_code(500);
        $errors[] = 'The animal could not be loaded. Please try again.';
    }
}

if ($animal) {
    foreach ($fields as $key => $field) {
        $values[$key] = (string) ($animal[$field['column']] ?? '');
    }

    $values['price'] = $animal['ListPrice'] === null
        ? ''
        : (string) $animal['ListPrice'];

    $saved = ($_SESSION['animal_updated_id'] ?? null) === $id;

    if ($saved) {
        unset($_SESSION['animal_updated_id']);
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
            $values[$key] = is_string($input) ? trim($input) : '';

            if ($field['required'] && $values[$key] === '') {
                $errors[] = $field['label'] . ' is required.';
            }

            if (strlen($values[$key]) > $field['max']) {
                $errors[] = $field['label'] . ' is too long.';
            }
        }

        $input = $_POST['price'] ?? '';
        $values['price'] = is_string($input) ? trim($input) : '';
        $price = null;

        if ($values['price'] !== '') {
            if (
                !is_numeric($values['price']) ||
                !is_finite((float) $values['price']) ||
                (float) $values['price'] < 0 ||
                (float) $values['price'] > 999999999.99
            ) {
                $errors[] = 'Enter a price between 0 and 999999999.99.';
            } else {
                $price = (float) $values['price'];
            }
        } elseif (!is_string($input)) {
            $errors[] = 'Invalid price input.';
        }

        if (!$errors) {
            try {
                $statement = $pdo->prepare(
                    'UPDATE Animal
                     SET Name = :name,
                         Breed = :breed,
                         Category = :category,
                         Color = :color,
                         ListPrice = :price
                     WHERE AnimalID = :id'
                );

                $statement->execute([
                    'name' => $values['name'],
                    'breed' => $values['breed'],
                    'category' => $values['category'],
                    'color' => $values['color'] === ''
                        ? null
                        : $values['color'],
                    'price' => $price,
                    'id' => $id,
                ]);

                $_SESSION['animal_updated_id'] = $id;

                header('Location: animal_edit.php?id=' . $id, true, 303);
                exit;

            } catch (PDOException $error) {
                error_log($error->getMessage());
                $errors[] = 'The changes could not be saved. Please try again.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';

?>

<section class="card">
    <h2>Edit animal</h2>

    <?php if ($saved): ?>
        <div class="notice success" role="status">
            Animal <?= (int) $id ?> was updated successfully.
            The form below shows the saved record.
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="notice error" role="alert">
            <p>Please check the following:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= editEscape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($animal): ?>
        <p>
            Animal ID: <?= (int) $id ?>.
            Registration and photo will remain unchanged.
        </p>

        <form id="animal-edit-form" method="post"
              action="animal_edit.php?id=<?= (int) $id ?>">

            <input type="hidden" name="csrf_token"
                   value="<?= editEscape($_SESSION['csrf_token']) ?>">

            <div class="form-grid">
                <?php foreach ($fields as $key => $field): ?>
                    <div class="field">
                        <label for="<?= editEscape($key) ?>">
                            <?= editEscape($field['label']) ?>
                            <?= $field['required'] ? '(required)' : '(optional)' ?>
                        </label>

                        <input id="<?= editEscape($key) ?>"
                               name="<?= editEscape($key) ?>"
                               type="text"
                               maxlength="<?= (int) $field['max'] ?>"
                               <?= $field['required'] ? 'required' : '' ?>
                               value="<?= editEscape($values[$key]) ?>">
                    </div>
                <?php endforeach; ?>

                <div class="field">
                    <label for="price">List price (optional)</label>

                    <input id="price" name="price" type="number"
                           min="0" max="999999999.99" step="any"
                           aria-describedby="price-help"
                           value="<?= editEscape($values['price']) ?>">

                    <small id="price-help">
                        Enter a non-negative price, or leave blank if not set.
                    </small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit">Save changes</button>
                <a class="button-secondary" href="animal_search.php">
                    Back to search
                </a>
            </div>
        </form>

    <?php else: ?>
        <a class="button-secondary" href="animal_search.php">
            Back to search
        </a>
    <?php endif; ?>
</section>

<script src="js/animal_edit.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>