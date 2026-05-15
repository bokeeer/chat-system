<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
$view_only = isset($_POST['view_only']) ? (int) $_POST['view_only'] : 0;

if (!$group_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Group ID']);
    exit;
}

try {
    // 1. Verify that user is owner or admin
    $stmt = $pdo->prepare("
        SELECT g.owner_id, gm.is_admin 
        FROM `groups` g
        JOIN group_members gm ON g.id = gm.group_id
        WHERE g.id = ? AND gm.user_id = ?
    ");
    $stmt->execute([$group_id, $current_user_id]);
    $perms = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$perms) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    $is_admin = ($perms['owner_id'] == $current_user_id) || ($perms['is_admin'] == 1);
    if (!$is_admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin required']);
        exit;
    }

    // 2. Update Settings
    $upd = $pdo->prepare("UPDATE `groups` SET view_only = ? WHERE id = ?");
    $upd->execute([$view_only, $group_id]);

    echo json_encode(['status' => 'Success', 'view_only' => $view_only]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>