<?php
$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$error_code = filter_var((int)($_GET['code'] ?? http_response_code()));
$current_file = basename($_SERVER['PHP_SELF']);



$error_messages = [
    404 => 'Page Not Found',
    403 => 'Forbidden Access',
    500 => 'Internal Server Error',
];

$error_sub_messages = [
    404 => 'We are not able to find what you are looking for',
    403 => 'Mind if you going back? You are not allowed to be here',
    500 => 'Sorry, looks like the server went on vacation',
];

$uri = ($current_file == 'download') ? 'Your requested file may not exist or has been deleted.' : $requestUri;
$error_message = $error_messages[$error_code] ?? 'An error occurred';
$error_sub_message = $error_sub_messages[$error_code] ?? 'An error occurred';

$exclude_nav = true;
include_once __DIR__ . '/layout/header.php';
?>

<style>
    body {
        background-color: #1c1c1e;
        color: #ffffff;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
        font-family: 'Helvetica Neue', sans-serif;
    }

    .error-container {
        text-align: center;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 40px 30px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        min-width: 750px;
    }

    h1 {
        font-size: 8rem;
        font-weight: bold;
        margin: 0;
        text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.3);
    }

    h2 {
        font-size: 2rem;
        margin: 20px 0;
    }

    p {
        font-size: 1.25rem;
        margin: 20px 0;
        opacity: 0.8;
    }

    .btn {
        background-color: #444;
        color: #ffffff;
        border-radius: 30px;
        padding: 10px 20px;
        transition: background-color 0.3s, transform 0.3s;
        text-decoration: none;
    }

    .btn:hover {
        background-color: #666;
        transform: translateY(-2px);
    }

    .footer {
        position: absolute;
        bottom: 20px;
        font-size: 0.8rem;
        color: #bbb;
    }
</style>

<body>
    <div class="error-container">
        <h1 class="display-1"><?= $error_code ?></h1>
        <h2>Oops! <?= $error_message ?>.</h2>
        <p><?= $error_sub_message ?></p>
        <p><?= htmlspecialchars($uri) ?></p>
        <a href="/home/index" class="btn">Return Home</a>
    </div>
    <div class="footer">©2007 - <?= date('Y'); ?> EnDecryp. All rights reserved.</div>
</body>

</html>