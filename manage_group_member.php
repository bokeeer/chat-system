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
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : ''; // 'kick', 'toggle_admin'

if (!$group_id || !$target_user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}

try {
    // 1. Check current user's permissions
    $stmt = $pdo->prepare("
        SELECT g.owner_id, gm.is_admin 
        FROM groups g
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

    $is_owner = ($perms['owner_id'] == $current_user_id);
    $is_admin = $is_owner || ($perms['is_admin'] == 1);
    
    // Check target user's info (if they are ALREADY a member)
    $stmt = $pdo->prepare("
        SELECT g.owner_id as g_owner, gm.is_admin as target_is_admin 
        FROM groups g
        JOIN group_members gm ON g.id = gm.group_id
        WHERE g.id = ? AND gm.user_id = ?
    ");
    $stmt->execute([$group_id, $target_user_id]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. Perform Action
    if ($action === 'add') {
        // Everyone in the group can add members now as per user request
        if ($target) {
            http_response_code(409);
            echo json_encode(['error' => 'User is already a member']);
            exit;
        }
        
        $ins = $pdo->prepare("INSERT INTO group_members (group_id, user_id, is_admin) VALUES (?, ?, 0)");
        $ins->execute([$group_id, $target_user_id]);
        echo json_encode(['status' => 'Success', 'action' => 'Added']);

    } elseif ($action === 'kick') {
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['error' => 'Admin required']);
            exit;
        }
        if (!$target) {
            http_response_code(404);
            echo json_encode(['error' => 'Target user is not a member']);
            exit;
        }
        // Prevent owner from being managed
        if ($target_user_id == $target['g_owner']) {
            http_response_code(403);
            echo json_encode(['error' => 'Owner cannot be managed']);
            exit;
        }
        // Only owner can kick admins. Admins can kick only members.
        if ($target['target_is_admin'] && !$is_owner) {
            http_response_code(403);
            echo json_encode(['error' => 'Admins can only be kicked by owner']);
            exit;
        }

        $del = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $del->execute([$group_id, $target_user_id]);
        echo json_encode(['status' => 'Success', 'action' => 'Kicked']);

    } elseif ($action === 'toggle_admin') {
        if (!$target) {
            http_response_code(404);
            echo json_encode(['error' => 'Target user is not a member']);
            exit;
        }
        // Prevent owner from being managed
        if ($target_user_id == $target['g_owner']) {
            http_response_code(403);
            echo json_encode(['error' => 'Owner cannot be managed']);
            exit;
        }
        // Only owner can toggle admin status
        if (!$is_owner) {
            http_response_code(403);
            echo json_encode(['error' => 'Only owner can manage admins']);
            exit;
        }

        $new_admin_val = $target['target_is_admin'] ? 0 : 1;
        $upd = $pdo->prepare("UPDATE group_members SET is_admin = ? WHERE group_id = ? AND user_id = ?");
        $upd->execute([$new_admin_val, $group_id, $target_user_id]);
        echo json_encode(['status' => 'Success', 'action' => $new_admin_val ? 'Promoted' : 'Demoted']);

    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
