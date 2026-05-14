<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if (!$token && !$group_id) {
    echo json_encode(['error' => 'Missing parameter']);
    exit;
}

try {
    if ($token) {
        $stmt = $pdo->prepare("SELECT id, name, is_channel, channel_type FROM groups WHERE join_token = ?");
        $stmt->execute([$token]);
    } else {
        $stmt = $pdo->prepare("SELECT id, name, is_channel, channel_type FROM groups WHERE id = ? AND channel_type = 'public'");
        $stmt->execute([$group_id]);
    }

    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        echo json_encode(['error' => 'Invalid link or group is private']);
        exit;
    }

    // Check if already a member
    $check = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
    $check->execute([$group['id'], $user_id]);
    
    if (!$check->fetch()) {
        $ins = $pdo->prepare("INSERT INTO group_members (group_id, user_id, is_admin) VALUES (?, ?, 0)");
        $ins->execute([$group['id'], $user_id]);
        $message = "Joined successfully";
    } else {
        $message = "Already a member";
    }

    echo json_encode([
        'status' => 'Success',
        'group_id' => $group['id'],
        'name' => $group['name'],
        'message' => $message
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
