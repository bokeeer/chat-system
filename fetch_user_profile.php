<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$me = (int)$_SESSION['user_id'];
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'User ID required']);
    exit;
}

try {
    // 1. Get basic info
    $stmt = $pdo->prepare("SELECT id, username, profile_pic, bio, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // 2. Get friend count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE (user_id = ? OR friend_id = ?) AND status = 'accepted'");
    $stmtCount->execute([$user_id, $user_id]);
    $user['friend_count'] = (int)$stmtCount->fetchColumn();

    // 3. Get friendship status with current user
    $stmtStatus = $pdo->prepare("
        SELECT id, status, user_id FROM friends 
        WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
    ");
    $stmtStatus->execute([$me, $user_id, $user_id, $me]);
    $friendship = $stmtStatus->fetch(PDO::FETCH_ASSOC);

    $user['friendship'] = null;
    if ($friendship) {
        $user['friendship'] = [
            'id' => $friendship['id'],
            'status' => $friendship['status'],
            'direction' => ($friendship['user_id'] == $me) ? 'sent' : 'received'
        ];
    }

    echo json_encode($user);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
