<?php

// Keeps upload logic out of main page. 

function saveAnimalPhoto(?array $file): ?string
{
    if ($file === null) {
        return null;
    }

    $error = $file['error'] ?? null;

    if (!is_int($error)) {
        throw new RuntimeException('Invalid upload request.');
    }

    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        throw new RuntimeException('Uploaded image must be 2 MB or smaller.');
    }

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please select the image again.');
    }

    $temporaryPath = $file['tmp_name'] ?? '';

    if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $size = filesize($temporaryPath);

    if ($size === false || $size === 0 || $size > 2 * 1024 * 1024) {
        throw new RuntimeException('Choose a non-empty image of 2 MB or smaller.');
    }

    if (!class_exists('finfo')) {
        throw new RuntimeException('Image checking is unavailable. Enable PHP fileinfo.');
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    ];

    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);

    if (!isset($allowedTypes[$mimeType])) {
        throw new RuntimeException('Only JPG, PNG and GIF images are accepted.');
    }

    if (@getimagesize($temporaryPath) === false) {
        throw new RuntimeException('The file could not be recognised as an image.');
    }

    $directory = dirname(__DIR__) . '/images';

    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('The images folder is missing or not writable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
    $relativePath = 'images/' . $filename;

    if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
        throw new RuntimeException('The image could not be saved.');
    }

    return $relativePath;
}