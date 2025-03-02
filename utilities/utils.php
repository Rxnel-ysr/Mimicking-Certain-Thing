<?php

/**
 * Class Encryptor
 * Contains methos of encryption and decryption
 */
class Encryptor
{
    const CIPHER_METHOD = "aes-256-cbc";
    const SALT_LENGTH = 16;
    const IV_LENGTH = 16;
    const KEY_LENGTH = 32;
    const ITERATIONS = 90000;

    /**
     * Encrypts data using AES-256-CBC with a password-derived key.
     *
     * @param string $data The plaintext data to be encrypted.
     * @param string $password The password used for key derivation and encryption.
     * @return string A base64-encoded string containing the salt, IV, and encrypted data.
     * @throws Exception If encryption fails.
     */
    public static function encrypt($data, $password)
    {

        $salt = openssl_random_pseudo_bytes(self::SALT_LENGTH);
        $key = hash_pbkdf2(
            "sha256",
            $password,
            $salt,
            self::ITERATIONS,
            self::KEY_LENGTH,
            true
        );
        $iv = openssl_random_pseudo_bytes(
            openssl_cipher_iv_length(self::CIPHER_METHOD)
        );

        // Capture potential errors using openssl_error_string()
        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        if ($encrypted === false) {
            throw new Exception("Encryption failed: " . openssl_error_string());
        }

        return base64_encode($salt . $iv . $encrypted);
    }

    /**
     * Decrypts data using AES-256-CBC with a password-derived key.
     *
     * @param string $data The base64-encoded string containing the salt, IV, and encrypted data.
     * @param string $password The password used to derive the decryption key.
     * @return string The decrypted plaintext data.
     * @throws Exception If the base64 decoding fails or decryption fails.
     */
    public static function decrypt($data, $password)
    {
        $data = base64_decode($data);
        if ($data === false) {
            throw new Exception("Invalid base64 string.");
        }
        $salt = substr($data, 0, self::SALT_LENGTH);
        $iv_length = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $iv = substr($data, self::SALT_LENGTH, $iv_length);
        $ciphertext = substr($data, self::SALT_LENGTH + $iv_length);
        $key = hash_pbkdf2(
            "sha256",
            $password,
            $salt,
            self::ITERATIONS,
            self::KEY_LENGTH,
            true
        );

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        if ($decrypted === false) {
            throw new Exception("Decryption failed. Check your password.");
        }

        return $decrypted;
    }
}

/**
 * Class Utils
 * Contains utilities for website operations.
 */
class Utils
{
    /**
     * List file that are not allowed.
     */
    private static $forbiddenFileExtensions = [];

    /**
     * Logs file location
     */
    private static $logFile = __DIR__ . "/../storage/logs/app.log";

    /**
     * Logs a message to the log file.
     *
     * @param string $message The message to log.
     * @return void
     */
    private static function log(string $message): void
    {
        file_put_contents(
            self::$logFile,
            "[" . date("Y-m-d H:i:s") . "] " . $message . "\n",
            FILE_APPEND
        );
    }

    /**
     * Searches for a file in a given directory.
     *
     * This function uses a recursive directory iterator to search for the specified file.
     *
     * @param string $filename The name of the file to search for.
     * @param string $root The root directory to start the search. Defaults to the parent directory of the current script.
     * @return string|null The relative path to the file or null if not found.
     *
     */
    public static function findFile(
        string $filename,
        string $root = __DIR__ . "/../asset"
    ): ?string {
        $directory = new RecursiveDirectoryIterator($root);
        $iterator = new RecursiveIteratorIterator($directory);

        foreach ($iterator as $file) {
            if ($file->getFilename() === $filename) {
                $relativePath = str_replace(
                    $_SERVER["DOCUMENT_ROOT"],
                    "",
                    $file->getPathname()
                );
                return "/" . ltrim($relativePath, "/");
            }
        }

        return null;
    }

    /**
     * Checks if the requested URI is restricted.
     *
     * If the requested URI matches any of the restricted paths, a 403 Forbidden response is sent,
     * and the error page is included.
     *
     * @param string $requestUri The requested URI to check.
     * @param array $restrictedPaths An array of restricted paths to compare against.
     * @return void
     */
    public static function checkRestrictedUri(
        string $requestUri,
        array $restrictedPaths
    ): void {
        foreach ($restrictedPaths as $path) {
            if (
                $requestUri === $path ||
                strpos($requestUri, $path . "/") === 0
            ) {
                self::log("Access denied for restricted URI: '{$requestUri}'");
                http_response_code(403);
                include_once dirname(__DIR__) . "/error.php";
            }
        }
    }

    /**
     * Serves a PHP file based on the requested URI or throws a 404 error if not found.
     *
     * @param string $requestUri The requested URI for the PHP file to include.
     * @return void
     */
    public static function serve(string $requestUri): void
    {
        $phpFile = dirname(__DIR__) . "/" . $requestUri . ".php";

        if (file_exists($phpFile) && is_file($phpFile)) {
            include_once $phpFile;
        } else {
            http_response_code(404);
            include_once dirname(__DIR__) . "/error.php";
        }
    }

    /**
     * Redirects the user to the homepage at /home/index if the specified request
     * URI matches any of the provided redirect paths.
     *
     * @param string $requestUri The URI of the current request to check.
     * @param array $redirect An array of paths to check against the current request URI.
     * @return void This function does not return a value; it terminates execution by redirecting.
     */
    public static function redirectHome(
        string $requestUri,
        array $redirect
    ): void {
        foreach ($redirect as $path) {
            if ($requestUri === $path) {
                self::log("Redirecting from '{$requestUri}' to '/home/index'");
                http_response_code(301);
                header("Location: /home/index");
                exit();
            }
        }
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
     * Setter for checkExcludedFileType. That sets the list of forbidden file extensions.
     *
     * @param array $extensions An array of file extensions to be set as forbidden.
     */
    public static function setExcludedFile(array $extensions)
    {
        self::$forbiddenFileExtensions = $extensions;
    }

    /**
     * Checks if the uploaded file type is excluded.
     *
     * @param string $filename The name of the file to check.
     * @throws Exception If the file type is excluded.
     */
    public static function checkExcludedFileType(string $filename)
    {
        $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($fileExtension, self::$forbiddenFileExtensions)) {
            self::log(
                "Attempted upload of excluded file type: '{$fileExtension}'"
            );
            throw new Exception(
                "The file type '{$fileExtension}' is not allowed."
            );
        }
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

}
