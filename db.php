<?php
// Temporary debug file - DELETE after testing
$host = 'yamanote.proxy.rlwy.net';
$port = '39296';
$dbname = 'railway';
$user = 'root';
$pass = 'FJLHLKHEtU0qfqVCZi0kAAxmPEJhhDSJ';

echo "<pre>";

// Test 1: With SSL disabled
echo "TEST 1: PDO with SSL verify disabled\n";
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $user, $pass,
        [
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
    echo "✅ SUCCESS\n\n";
} catch (PDOException $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 2: With SSL CA set to empty (force SSL connection)
echo "TEST 2: PDO with SSL CA empty\n";
try {
    $pdo2 = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8",
        $user, $pass,
        [
            PDO::MYSQL_ATTR_SSL_CA => '',
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
    echo "✅ SUCCESS\n\n";
} catch (PDOException $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 3: mysqli instead of PDO
echo "TEST 3: mysqli connection\n";
if (function_exists('mysqli_connect')) {
    $conn = @mysqli_connect($host, $user, $pass, $dbname, (int)$port);
    if ($conn) {
        echo "✅ SUCCESS\n\n";
        mysqli_close($conn);
    } else {
        echo "❌ FAILED: " . mysqli_connect_error() . "\n\n";
    }
} else {
    echo "⚠️ mysqli not available\n\n";
}

// Test 4: mysqli with SSL
echo "TEST 4: mysqli with SSL\n";
if (function_exists('mysqli_init')) {
    $conn2 = mysqli_init();
    mysqli_ssl_set($conn2, NULL, NULL, NULL, NULL, NULL);
    $result = @mysqli_real_connect($conn2, $host, $user, $pass, $dbname, (int)$port, NULL, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
    if ($result) {
        echo "✅ SUCCESS\n\n";
        mysqli_close($conn2);
    } else {
        echo "❌ FAILED: " . mysqli_connect_error() . "\n\n";
    }
} else {
    echo "⚠️ mysqli_init not available\n\n";
}

echo "</pre>";
?>
