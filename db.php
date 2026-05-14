<?php
// Temporary debug file - DELETE after testing
echo "<pre>";
echo "DB_HOST: [" . getenv('DB_HOST') . "]\n";
echo "DB_NAME: [" . getenv('DB_NAME') . "]\n";
echo "DB_USER: [" . getenv('DB_USER') . "]\n";
echo "DB_PASS: [" . getenv('DB_PASS') . "]\n";
echo "DB_PORT: [" . getenv('DB_PORT') . "]\n";
echo "---\n";
echo "_ENV DB_HOST: [" . ($_ENV['DB_HOST'] ?? 'NOT SET') . "]\n";
echo "_ENV DB_PASS: [" . ($_ENV['DB_PASS'] ?? 'NOT SET') . "]\n";
echo "_SERVER DB_HOST: [" . ($_SERVER['DB_HOST'] ?? 'NOT SET') . "]\n";
echo "</pre>";

// Try connection with hardcoded Railway values for testing
try {
    $pdo = new PDO(
        "mysql:host=yamanote.proxy.rlwy.net;port=39296;dbname=railway;charset=utf8",
        "root",
        "FJLHLKHEtU0qfqVCZi0kAAxmPEJhhDSJ"
    );
    echo "HARDCODED CONNECTION: SUCCESS ✅";
} catch (PDOException $e) {
    echo "HARDCODED CONNECTION FAILED: " . $e->getMessage();
}
?>
