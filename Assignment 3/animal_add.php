<?php

session_start();

require __DIR__ . '/db.php';
require __DIR__ . '/includes/upload.php';

$errors = [];

$savedId = $_SESSION['animal_saved_id'] ?? null;
unset($_SESSION['animal_saved_id']);

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
        $photoPath = null;

        try {
            $photoPath = saveAnimalPhoto($_FILES['photo'] ?? null);

            $statement = $pdo->prepare(
                'INSERT INTO Animal (Name, Breed, Category, Registered, Photo)
                VALUES (:name, :breed, :category, :registered, :photo)'
            );

            $statement->execute([
                'name' => $values['name'],
                'breed' => $values['breed'],
                'category' => $values['category'],
                'registered' => $values['registered'] === ''
                    ? null
                    : $values['registered'],
                'photo' => $photoPath,
            ]);

            $_SESSION['animal_saved_id'] = (int) $pdo->lastInsertId();

            header('Location: animal_add.php', true, 303);
            exit;

        } catch (PDOException $error) {
            if ($photoPath !== null) {
                $absolutePath = __DIR__ . '/' . $photoPath;

                if (is_file($absolutePath)) {
                    unlink($absolutePath);
                }
            }

            error_log($error->getMessage());
            $errors[] = 'The animal could not be saved, please try again.';

        } catch (RuntimeException $error) {
            $errors[] = $error->getMessage();
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

    <form id="animal-form" method="post" action="animal_add.php" enctype="multipart/form-data">
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
                    For example, AKC or CFA. Can leave blank if unregistered.
                </small>
            </div>

            <div class="field">
                <label for="photo">Animal photo (optional)</label>

                <input id="photo" name="photo" type="file"
                    accept="image/jpeg,image/png,image/gif"
                    aria-describedby="photo-help photo-error">

                <small id="photo-help">
                    JPG, PNG or GIF. Maximum 2 MB.
                    After an unsuccessful submission, please select your image again.
                </small>

                <small id="photo-error" class="field-error"
                    aria-live="polite"></small>
            </div>

        </div>

        <button type="submit">Save animal</button>
    </form>
</section>

<script src="js/animal_add.js" defer></script>

<?php require __DIR__ . '/includes/footer.php'; ?>