<?php
require_once 'definitions.php';
require_once UTILS_PATH . 'Utility.php';
require_once UTILS_PATH . 'Env.php';
Env::load(ROOT . '.env');

requireOnce([
    UTILS_PATH,
    ROOT . '/model',
    ROOT . '/routes'
], [
    'Utility.php',
    'Env.php'
]);

/**
 * Serve get request
 * 
 * Note: I've tried to place it some where to make my code looks cleaner but it doesn't work as expected, I'll figured a way later
 * */
$prohibitedTypes = explode(',', getenv('GET_DENIED'));
$allowedTypes = explode(',', getenv('GET_ALLOWED'));

$strictMode = filter_var(getenv('GET_STRICT'), FILTER_VALIDATE_BOOLEAN);

// error_log(($strictMode) ? 'yeah' : 'nah'); 
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = ROOT . $path;


    if (file_exists($file) && is_file($file)) {
        $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $fileName = basename($file);

        $isProhibited = in_array($fileType, $prohibitedTypes);
        $isAllowed = in_array($fileType, $allowedTypes);

        // Strict mode: block anything not explicitly allowed
        if (($isProhibited || (!$isAllowed && $strictMode)) && $fileName !== 'download.php') {
            Utils::log("WARN - User is a little curious at '{$path}'");

            $message = match (true) {
                $fileName === 'error.php' => 'Do you really like to see error page so much?',
                $fileType === 'php' => 'You can access them normally in a webpage, why here?',
                default => 'We are sorry but what you have requested is not allowed<br /> <p style="font-size:15px;">Unless if you ask me nicely in person</p>',
            };

            Utils::showErrorPage(403, 'Are you curious little fella?', $message, 'Got Curious?');
            return true;
        }

        return false;
    }
}

// spl_autoload_register(function ($className) {
//     $filePath = __DIR__ . '/' . str_replace('\\', '/', $className) . '.php';
//     if (file_exists($filePath)) {
//         require_once $filePath;
//     }
// });

# Bagian yang dilarang
restrictedUri(['/public', '/storage', '/resources']);

# Redirect home jika nyasar
redirectHome([
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
    '/',
]);

// Utils::showErrorPage(
//     404,
//     'Oppsie Whopsie',
//     'Perhaps are you lost? Let me bring you home',
//     'Lost?'
// );
