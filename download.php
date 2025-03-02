<?php
require_once __DIR__ . '/utilities/utils.php';

$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

/**
 *  setahu saya buat xss protection, tapi beneran butuh atau gk sih?
 *  kalau saya include, css dan js nya hilang
 */
// header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'");

// buat fixed dir, biar gk salah download
define('ENCRYPTED_FILES_DIR', __DIR__ . '/storage/download/');

// download logic
if (isset($_GET['file'])) {
    $filename = basename($_GET['file']);
    $filePath = ENCRYPTED_FILES_DIR . $filename;

    if (realpath($filePath) && strpos(realpath($filePath), realpath(ENCRYPTED_FILES_DIR)) === 0 && file_exists($filePath)) {
        ob_end_clean();
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);


        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        finfo_close($finfo);
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        flush();
        readfile($filePath);

        unlink($filePath);
        exit;
    } else {
        http_response_code(404);
        include_once __DIR__ . '/error.php';
    }
} else {
    echo "<p>No file specified.</p>";
}
