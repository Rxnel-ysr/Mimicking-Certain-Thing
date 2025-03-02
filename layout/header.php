<?php
/**
* DRY (Don't Repeat Yourself)
*/
include_once __DIR__ . '/../utilities/utils.php';


$current_file = Utils::getBaseName($_SERVER['PHP_SELF']);
$cssFile = Utils::findFile('main.css');
$jsFile = Utils::findFile('main.js');

$title = [
    'index' => 'EnDecryp - Encrypt or Decrypt File',
    'encryp' => 'Encryption - EnDecryp',
    'decryp' => 'Decryption - EnDecryp'
];

$title_name = isset($title[$current_file]) ? $title[$current_file] : 'An error occurred';

$exclude_nav = isset($exclude_nav) && $exclude_nav;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= Utils::sanitize($cssFile) ?>">
    <title><?= $title_name ?></title>
</head>

<body>

    <script>
        console.log(<?= $jsFile ? '"JavaScript file loaded at:\n' . addslashes($jsFile) . '"' : '"JavaScript file not found"'; ?>);
        console.log(<?= $cssFile ? '"CSS file loaded at:\n' . addslashes($cssFile) . '"' : '"CSS file not found"'; ?>);
    </script>

    <?php
    if (!$exclude_nav):
    ?>
        <!-- navbar daur ulang -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="/home/index">EnDeryp</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_file == 'encryp') ? 'active' : ''; ?>" href="/home/encryp">Encrypt File</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_file == 'decryp') ? 'active' : ''; ?>" href="/home/decryp">Decrypt File</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

    <?php
    endif;
    ?>