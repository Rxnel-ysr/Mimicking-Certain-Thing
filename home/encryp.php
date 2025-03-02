<?php include_once dirname(__DIR__) . '/layout/header.php'; ?>

<main class="container">
    <div class="encryption-container">
        <h2 class="text-center mt-5 text-dark">File Encryption</h2>
        <form id="encryptionForm" action="encryp" method="post" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label for="file" class="form-label">Choose file to encrypt:</label>
                <input type="file" name="file" id="file" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="encryptCount" class="form-label">Encryption Count:</label>
                <input type="number" name="encryptCount" id="encryptCount" min="1" class="form-control" oninput="generatePasswordFields()">
            </div>

            <div id="passwordFields"></div>
            <button type="submit" class="btn btn-primary btn-block">Encrypt File</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (isset($_FILES['file']) && isset($_POST['encryptCount'])) {
                    $fileTmpPath = $_FILES['file']['tmp_name'];
                    $password = $_POST['password'];
                    $encryptCount = $_POST['encryptCount'];
                    $filename = Utils::getBaseName($_FILES['file']['name']);
                    Utils::checkExcludedFileType($filename);

                    $data = file_get_contents($fileTmpPath);

                    if ($data === false) {
                        echo "<br><div class='alert alert-danger'><span class='badge bg-danger'>Error</span> Cannot read file, please upload another file.</div>";
                    }

                    for ($i = 0; $i <= $encryptCount; $i++) {
                        $data = Encryptor::encrypt($data, $password[$i]);
                    }

                    $encryptedFilename = dirname(__DIR__) . '/storage/download/' . $filename . '.enc';
                    file_put_contents($encryptedFilename, $data . "\n----------\n[Info] This file has been encrypted {$encryptCount} time(s).");

                    $sanitized_file = Utils::getBaseName($encryptedFilename);
                    echo "<br><div class='alert alert-success'><span class='badge bg-success'>Done</span> File decrypted successfully. <a href='../download?file=" . Utils::sanitize($sanitized_file) . "' class='alert-link' download>Download Encrypted file</a></div>";
                } else {
                    echo "<br><div class='alert alert-danger'><span class='badge bg-danger'>Error</span> Please upload a file and provide all required inputs.</div>";
                }
            } catch (Exception $e) {
                echo "<br><div class='alert alert-danger'><span class='badge bg-danger'>Error</span> " . $e->getMessage() . "</div>";
            } catch (ValueError) {
                echo "<br><div class='alert alert-danger'><span class='badge bg-danger'>Error</span> Cannot read file, please upload another file.</div>";
            }
        }
        ?>
    </div>
</main>

<?php include_once dirname(__DIR__) . '/layout/footer.php'; ?>