<?php

require __DIR__ . '/db.php';

$errors = [];
$savedId = null;

$values = [
    'name' => '',
    'breed' => '',
    'category' => '',
    'registered' => '',
];

$limits = [
    'name' => 50,
    'breed' => 50,
    'category' => 50,
    'registered' => 10,
];

function escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $unused) {
        $input = $_POST[$field] ?? '';
        $values[$field] = is_string($input) ? trim($input) : '';

        if ($field !== 'registered' && $values[$field] === '') {
            $errors[] = ucfirst($field) . ' is required.';
        }

        if (strlen($values[$field]) > $limits[$field]) {
            $errors[] = ucfirst($field) . ' is too long.';
        }
    }

    if (!$errors) {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO Animal (Name, Breed, Category, Registered)
                 VALUES (:name, :breed, :category, :registered)'
            );

            $statement->execute([
                'name' => $values['name'],
                'breed' => $values['breed'],
                'category' => $values['category'],
                'registered' => $values['registered'] === ''
                    ? null
                    : $values['registered'],
            ]);

            $savedId = (int) $pdo->lastInsertId();

            foreach ($values as $field => $unused) {
                $values[$field] = '';
            }
        } catch (PDOException $error) {
            error_log($error->getMessage());
            $errors[] = 'The animal could not be saved. Please try again.';
        }
    }
}

require __DIR__ . '/includes/header.php';

?>

<section class="card">
    <h2>Add an animal</h2>
    <p>Enter the animal’s details. Registration is optional.</p>

    <?php if ($savedId !== null): ?>
        <div class="notice success" role="status">
            Animal saved successfully. Animal ID: <?= $savedId ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="notice error" role="alert">
            <p>Please correct the following:</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= escape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="animal_add.php">
        <div class="form-grid">
            <div class="field">
                <label for="name">Animal name (required)</label>
                <input id="name" name="name" type="text"
                       maxlength="50" required
                       value="<?= escape($values['name']) ?>">
            </div>

            <div class="field">
                <label for="breed">Breed (required)</label>
                <input id="breed" name="breed" type="text"
                       maxlength="50" required
                       value="<?= escape($values['breed']) ?>">
            </div>

            <div class="field">
                <label for="category">Category (required)</label>
                <input id="category" name="category" type="text"
                       maxlength="50" required
                       placeholder="e.g. Dog, Cat, Bird"
                       value="<?= escape($values['category']) ?>">
            </div>

            <div class="field">
                <label for="registered">Registration organisation</label>
                <input id="registered" name="registered" type="text"
                       maxlength="10" aria-describedby="registration-help"
                       value="<?= escape($values['registered']) ?>">
                <small id="registration-help">
                    For example, AKC or CFA. Leave blank if unregistered.
                </small>
            </div>
        </div>

        <button type="submit">Save animal</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>