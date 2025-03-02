<?php
require_once __DIR__ . '/utilities/utils.php';


// Ngambil url
$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$file = __DIR__ . $requestUri;

// jangan sentuh kalau tidak mau error dimana mana
if (preg_match('/\.(css|js|jpg|jpeg|png|gif|mp4)$/', $requestUri)) {
    $filePath = __DIR__ . $requestUri;
    if (file_exists($filePath)) {
        return false;
    }
}

// Redirect user agar tidak nyasar
Utils::redirectHome($requestUri, [
    '/hmoe',
    '/hoem',
    '/homee',
    '/hoem/',
    '/homa',
    '/hom',
    '/homne',
    '/Home',
    '/INDEX',
    '/Home/index',
    '/home/',
    '//home',
    '///home/',
    '/home/index/',
    'home',
    'home/',
    '/home/1',
    '/home2',
    '/home/index.html',
    '/home/index.php',
    '/home/home',
    '/?home',
    '/#home',
    '/home?query=test',
    '/home&action=view',
    '/?p=home',
    '/home%20',
    '/ho%me',
    '/home%23',
]);

//  Cegah user agar tidak nyasar kesini
Utils::checkRestrictedUri($requestUri, $restrictedPaths = [
    '/utilities',
    '/storage',
    '/layout',
    '/asset'
]);


// File yang di larang diupload
Utils::setExcludedFile([
    'exe',
    'dat',
    'sh'
]);

Utils::serve($requestUri);
