<?php

require __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function getFilter(string $key, int $maxLength): string
{
    $value = $_GET[$key] ?? '';

    if (!is_string($value) || strlen($value) > $maxLength) {
        return '';
    }

    return trim($value);
}

function animalPhoto(?string $path): ?string
{
    if (!$path) {
        return null;
    }

    // Only allow image filenames directly inside our images folder
    if (!preg_match(
        '~\Aimages/[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|gif)\z~i',
        $path
    )) {
        return null;
    }

    $absolutePath = __DIR__ . '/' . $path;

    if (!is_file($absolutePath) || @getimagesize($absolutePath) === false) {
        return null;
    }

    return $path;
}

$name = getFilter('name', 50);
$category = getFilter('category', 50);
$registered = getFilter('registered', 20);

if (!in_array($registered, ['', 'registered', 'unregistered'], true)) {
    $registered = '';
}

$animals = [];
$categories = [];
$errorMessage = '';

try {
    $categories = $pdo->query(
        "SELECT DISTINCT Category
         FROM Animal
         WHERE Category IS NOT NULL AND TRIM(Category) <> ''
         ORDER BY Category"
    )->fetchAll(PDO::FETCH_COLUMN);

    $conditions = [];
    $parameters = [];

    if ($name !== '') {
        $conditions[] = "Name LIKE :name ESCAPE '!'";
        $parameters['name'] = '%' . strtr($name, [
            '!' => '!!',
            '%' => '!%',
            '_' => '!_',
        ]) . '%';
    }

    if ($category !== '') {
        $conditions[] = 'Category = :category';
        $parameters['category'] = $category;
    }

    if ($registered === 'registered') {
        $conditions[] = "Registered IS NOT NULL AND TRIM(Registered) <> ''";
    } elseif ($registered === 'unregistered') {
        $conditions[] = "(Registered IS NULL OR TRIM(Registered) = '')";
    }

    $sql = 'SELECT AnimalID, Name, Category, Breed,
                   Registered, Color, ListPrice, Photo
            FROM Animal';

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY AnimalID DESC';

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    $animals = $statement->fetchAll();

} catch (PDOException $error) {
    error_log($error->getMessage());
    $errorMessage = 'Animals could not be loaded. Please try again later.';
}

require __DIR__ . '/includes/header.php';

?>

<section class="card">
    <h2>Search animals</h2>
    <p>Use any combination of filters, or leave them blank to see all animals.</p>

    <form method="get" action="animal_search.php">
        <div class="form-grid">
            <div class="field">
                <label for="name">Animal name</label>
                <input id="name" name="name" type="search"
                       maxlength="50" placeholder="Full or partial name"
                       value="<?= e($name) ?>">
            </div>

            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>

                    <?php foreach ($categories as $option): ?>
                        <option value="<?= e($option) ?>"
                            <?= $category === $option ? 'selected' : '' ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="registered">Registration status</label>
                <select id="registered" name="registered">
                    <option value="">All animals</option>
                    <option value="registered"
                        <?= $registered === 'registered' ? 'selected' : '' ?>>
                        Registered
                    </option>
                    <option value="unregistered"
                        <?= $registered === 'unregistered' ? 'selected' : '' ?>>
                        Unregistered
                    </option>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit">Search animals</button>
            <a class="button-secondary" href="animal_search.php">
                Clear filters
            </a>
        </div>
    </form>
</section>

<section class="card results-section">
    <h2>Results</h2>

    <?php if ($errorMessage !== ''): ?>
        <div class="notice error" role="alert">
            <?= e($errorMessage) ?>
        </div>

    <?php elseif (!$animals): ?>
        <p>No animals match your filters. Try a different search.</p>

    <?php else: ?>
        <p>
            <?= count($animals) ?>
            <?= count($animals) === 1 ? 'animal found' : 'animals found' ?>.
        </p>

        <div class="table-wrapper" tabindex="0"
             role="region" aria-label="Animal search results">
            <table>
                <caption>Animals matching the selected filters</caption>
                <thead>
                    <tr>
                        <th scope="col">Photo</th>
                        <th scope="col">ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Category</th>
                        <th scope="col">Breed</th>
                        <th scope="col">Registration</th>
                        <th scope="col">Colour</th>
                        <th scope="col">List price</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($animals as $animal): ?>
                        <?php $photo = animalPhoto($animal['Photo']); ?>

                        <tr>
                            <td>
                                <?php if ($photo !== null): ?>
                                    <img class="animal-thumbnail"
                                         src="<?= e($photo) ?>"
                                         alt="<?= e(
                                             'Photo of ' .
                                             ($animal['Name'] ?: 'unnamed animal')
                                         ) ?>"
                                         width="80" height="80"
                                         loading="lazy">
                                <?php else: ?>
                                    <span class="photo-placeholder">
                                        No photo
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td><?= (int) $animal['AnimalID'] ?></td>
                            <td><?= e($animal['Name'] ?: 'Unnamed') ?></td>
                            <td><?= e($animal['Category'] ?: 'Not specified') ?></td>
                            <td><?= e($animal['Breed'] ?: 'Not specified') ?></td>
                            <td><?= e($animal['Registered'] ?: 'Unregistered') ?></td>
                            <td><?= e($animal['Color'] ?: 'Not specified') ?></td>
                            <td class="price">
                                <?= $animal['ListPrice'] === null
                                    ? 'Not set'
                                    : '$' . number_format(
                                        (float) $animal['ListPrice'], 2
                                    ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>