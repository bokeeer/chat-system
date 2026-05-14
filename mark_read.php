<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : null;
$contact_id = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : null;

try {
    if ($group_id) {
        // Mark group read: Update last_read_id to the max id in group_messages
        $maxStmt = $pdo->prepare("SELECT MAX(id) FROM group_messages WHERE group_id = ?");
        $maxStmt->execute([$group_id]);
        $maxId = (int)$maxStmt->fetchColumn();
        
        if ($maxId > 0) {
            $upd = $pdo->prepare("UPDATE group_members SET last_read_id = ? WHERE group_id = ? AND user_id = ?");
            $upd->execute([$maxId, $group_id, $user_id]);
        }
    } elseif ($contact_id) {
        // Mark private messages as read
        $upd = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ? AND receiver_id = ?");
        $upd->execute([$contact_id, $user_id]);
    }
    
    echo json_encode(['status' => 'Success']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
