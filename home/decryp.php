<?php include_once dirname(__DIR__) . '/layout/header.php'; ?>

<main class="container">
    <div class="decryption-container">
        <h2 class="text-center text-dark">File Decryption</h2>

        <form id="decryptionForm" action="" method="post" enctype="multipart/form-data" class="mt-4">
            <div class="mb-3">
                <label for="file" class="form-label">Choose file to decrypt:</label>
                <input type="file" name="file" id="file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Confirm</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $filename = $_FILES['file']['name'];

            $encryptedData = file_get_contents($fileTmpPath);
            $parts = explode("\n----------\n", $encryptedData);

            if (count($parts) < 2) {
                echo "<br><div class='alert alert-danger'><span class='badge bg-danger'>Error</span> Invalid encrypted file format.</div>";
                exit;
            }

            $encryptedSessions = $parts[0];
            $infoLine = trim($parts[1]);

            preg_match('/\d+/', $infoLine, $matches);
            $sessionCount = (int)$matches[0];

            echo "<br><div class='alert alert-info'><span class='badge bg-info'>Info</span> Detected {$sessionCount} encryption sessions.</div>";

            echo "<form action='' method='post' enctype='multipart/form-data' class='mt-4'>";
            echo "<input type='hidden' name='sessionCount' value='{$sessionCount}'>";
            echo "<input type='hidden' name='fileName' value='{$filename}'>";
            echo "<input type='hidden' name='encryptedSessions' value='" . base64_encode($encryptedSessions) . "'>";

            echo "<div id='passwordFields'>";

            for ($i = 1; $i <= $sessionCount; $i++) {
                echo "<div class='mb-3 password-field'>";
                echo "<label for='password{$i}' class='form-label'>Password {$i}:</label>";
                echo "<input type='password' name='password[]' id='password{$i}' class='form-control' required>";
                echo "</div>";
            }

            echo "</div>";
            echo "<button type='submit' class='btn btn-primary btn-block'>Decrypt File</button>";
            echo "</form>";
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sessionCount'])) {
            $decryptCount = intval($_POST['sessionCount']);
            $filename = $_POST['fileName'];
            $encryptedSessions = base64_decode($_POST['encryptedSessions']);

            try {
                for ($i = $decryptCount; $i >= 1; $i--) {
                    if (isset($_POST['password'][$i - 1])) {
                        // sanitize input ygy
                        $password = Utils::sanitize(trim($_POST['password'][$i - 1]), ENT_QUOTES, 'UTF-8');

                        // Decrypt datanya
                        $decryptedData = Encryptor::decrypt($encryptedSessions, $password);
                    } else {
                        echo "<br><div class='alert alert-danger'><span class='badge bg-danger'>Error</span> Missing password for decryption round {$i}.</div>";
                        exit;
                    }
                }

                $decryptedFilename = __DIR__ . '/../storage/download' . preg_replace('/\.enc$/', '', $filename);
                if (file_put_contents($decryptedFilename, $decryptedData) === false) {
                    echo "<br><div class='alert alert-danger'><span class='badge bg-danger'>Error</span> Failed to save the decrypted file.</div>";
                } else {
                    $sanitized_file = Utils::getBaseName($decryptedFilename);
                    echo "<br><div class='alert alert-success'><span class='badge bg-success'>Done</span> File decrypted successfully. <a href='../download?file=" . Utils::sanitize($decryptedFilename) . "' class='alert-link' download>Download decrypted file</a></div>";
                }
            } catch (Exception $e) {
                echo "<br><div class='alert alert-danger'><span class='badge bg-danger'>Error</span> " . Utils::sanitize($e->getMessage()) . "</div>";
            }
        }
        ?>
    </div>
</main>

<?php include_once dirname(__DIR__) . '/layout/footer.php'; ?>