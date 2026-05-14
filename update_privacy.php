<?php
session_start();
require 'db.php';

$user_id = $_SESSION['user_id'] ?? (isset($_REQUEST['user_id']) ? (int)$_REQUEST['user_id'] : null);

if (!$user_id) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT is_private FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    echo json_encode(['is_private' => (int)$user['is_private']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_private = isset($_POST['is_private']) ? (int)$_POST['is_private'] : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_private = ? WHERE id = ?");
        $stmt->execute([$is_private, $user_id]);
        echo json_encode(['status' => 'Success']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>
