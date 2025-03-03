<?php
$logFile = ROOT . "/storage/logs/server.log";
$resources = ROOT . "/resources/";
$views = ROOT . "/resources/views";
$ErrorPage = ROOT . "/error.php";
$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
// include_once UTILS_PATH . 'qu.php';

/**
 * Will include given views when the URL is matched or execute a callback.
 */
function route($uri, $viewOrCallback): void
{
    global $views;
    global $requestUri;

    if ($requestUri === $uri) {
        if (is_callable($viewOrCallback)) {
            $viewOrCallback(); // Execute the callback
        } else {
            $viewPath = $views . "/" . $viewOrCallback . ".php";
            if (file_exists($viewPath)) {
                include_once $viewPath;
            } else {
                Utils::showErrorPage(404);
            }
        }
        exit();
    }
}

/**
 * Same like route, bu for backend
 */
function backRoute($uri, $part): void
{
    global $resources;
    global $requestUri;
    if ($requestUri === $uri) {
        $backPart = $resources . "/" . $part . ".php";

        if (file_exists($backPart)) {

            include_once $backPart;
            exit();
        } else {
            Utils::showErrorPage(404);
        }
    }
}

$data = [
    'nama' => "Anwar"
];

function includeView($view, array $data = []): void
{
    global $views;
    $viewPath = $views . "/" . $view . ".php";

    if (file_exists($viewPath)) {
        extract($data);
        include $viewPath;
    } else {
        echo "View not found: " . $viewPath;
        exit();
    }
}

function asset(string $path): string
{
    $protocol =
        !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
        ? "https"
        : "http";

    $host = $_SERVER["HTTP_HOST"];

    $path = ltrim($path, "/");
    return "{$protocol}://{$host}/public/{$path}";
}

function media(string $path): string
{
    $protocol =
        !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
        ? "https"
        : "http";

    $host = $_SERVER["HTTP_HOST"];

    $path = ltrim($path, "/");
    return "{$protocol}://{$host}/storage/{$path}";
}

function restrictedUri(array $restrictedUris): void
{
    global $requestUri;
    if (in_array($requestUri, $restrictedUris)) {
        Utils::log(
            "WARN - User tried to access prohibited uri: '{$requestUri}'"
        );
        Utils::showErrorPage(403);
    }
}


function redirectHome(array $redirect): void
{
    global $requestUri;
    if (in_array($requestUri, $redirect)) {
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Location: /home", true, 301);
        Utils::log("OK - Redirecting user from '{$requestUri}' to '/home'");
        exit();
    }
}

function redirectBack(): void
{
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: " . $_SERVER['HTTP_REFERER'], true, 303);
    exit;
}

function printAsJson($data, $additionalOption)
{
    header("Content-type: application/json");
    echo json_encode($data, $additionalOption);
}

function requireOnce(array|string $paths, array|string $excepts = [])
{
    $excepts = array_map('realpath', (array)$excepts); // Normalize paths

    foreach ((array)$paths as $path) {
        if (is_dir($path)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($files as $file) {
                $filepath = $file->getPathname();
                if ($file->isFile() && !in_array(realpath($filepath), $excepts)) {
                    require_once $filepath;
                }
            }
        } elseif (is_file($path) && !in_array(realpath($path), $excepts)) {
            require_once $path;
        }
    }
}

class Utils
{

    public static function showErrorPage(
        int $errorCode,
        string $customMessage = "",
        string $customSubMessage = "",
        string $customTitleName = ""
    ): void {
        global $ErrorPage;
        http_response_code($errorCode);

        $error_messages = [
            404 => "Page Not Found",
            403 => "Forbidden Access",
            500 => "Internal Server Error",
        ];

        $error_sub_messages = [
            404 => "We are not able to find what you are looking for",
            403 => "Mind if you going back? You are not allowed to be here",
            500 => "Sorry, looks like the server went on vacation",
        ];

        $title_name = [
            404 => "Not found Error",
            403 => "Probihited action",
            500 => "Server Error",
        ];

        $error_message =
            $customMessage ?: $error_messages[$errorCode] ?? "An error occurred";
        $error_sub_message =
            $customSubMessage ?:
            $error_sub_messages[$errorCode] ?? "An error occurred";
        $title_name =
            $customTitleName ?: $title_name[$errorCode] ?? "An error occured";

        include_once $ErrorPage;
        exit();
    }

    /**
     * Sanitizes input data by converting special characters to HTML entities.
     *
     * @param string $data The input data to sanitize.
     * @return string The sanitized string.
     */
    public static function sanitize(string $data): string
    {
        return htmlspecialchars($data, ENT_QUOTES, "UTF-8");
    }

    /**
     * Safely retrieves the basename of a given path.
     *
     * @param string $data The path to retrieve the basename from.
     * @param string $suffix Optional. A suffix to be removed from the basename.
     * @return string The basename of the path.
     * @throws Exception If the provided path is not a valid string or does not contain a filename.
     */
    public static function getBaseName(
        string $data,
        string $suffix = ""
    ): string {
        if (!is_string($data) || empty($data)) {
            throw new Exception(
                "The provided file does not contain a valid filename."
            );
        }

        $baseName = basename($data, $suffix);

        if ($baseName === "") {
            throw new Exception("Invalid filename provided.");
        }

        return $baseName;
    }

    /**
     * Refresh current page
     *
     * @param int $delay Delay for refresh
     * @return void
     */
    public static function refresh(int $delay)
    {
        header("refresh: " . $delay);
        exit();
    }

    /**
     * Logs a message to the log file.
     *
     * @param string $message The message to log.
     * @return void
     */
    public static function log(string $message): void
    {
        $user = self::getUserInfo();
        global $logFile;
        file_put_contents(
            $logFile,
            "[" .
                date("Y-m-d H:i:s") .
                "] { " .
                $message .
                " - " .
                $user .
                " }\n",
            FILE_APPEND
        );
    }

    /**
     * Collects user information and returns it as a string.
     *
     * @return string The collected user information.
     */
    public static function getUserInfo(): string
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }

        $userAgent = $_SERVER["HTTP_USER_AGENT"];

        $referrer = isset($_SERVER["HTTP_REFERER"])
            ? $_SERVER["HTTP_REFERER"]
            : "No referrer";

        $protocol =
            !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off"
            ? "https"
            : "http";

        $currentUrl =
            $protocol . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

        $acceptedLanguages = $_SERVER["HTTP_ACCEPT_LANGUAGE"] ?? 'N\A';

        $requestMethod = $_SERVER["REQUEST_METHOD"];

        $requestTime = $_SERVER["REQUEST_TIME"];

        return "User Info: 'IP: $ip, User-Agent: $userAgent, Referrer: $referrer, URL: $currentUrl, Accepted Languages: $acceptedLanguages, Request Method: $requestMethod, Request Time: $requestTime'";
    }
}
