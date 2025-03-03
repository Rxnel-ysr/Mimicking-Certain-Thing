<?php
$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
# Routes
route("/home", "index");
route("/media", "list");

route('/raw', 'raw');

Route::get('/yuhu', function () {
    echo "yaaya";
});
$start = microtime(true);
Route::dispatch($requestUri);
$end = microtime(true);

echo 'Execution time: ' . round(($end - $start) * 1000, 4) . ' ms';
