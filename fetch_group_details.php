<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if (!$group_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Group ID']);
    exit;
}

try {
    // 1. Fetch Group Metadata
    $stmt = $pdo->prepare("SELECT id, name, owner_id, is_channel, channel_type, description, profile_pic, join_token, view_only FROM groups WHERE id = ?");
    $stmt->execute([$group_id]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        http_response_code(404);
        echo json_encode(['error' => 'Group not found']);
        exit;
    }

    // 2. Verify Membership and get role
    $stmt = $pdo->prepare("SELECT is_admin FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->execute([$group_id, $current_user_id]);
    $membership = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$membership) {
        http_response_code(403);
        echo json_encode(['error' => 'Not a member']);
        exit;
    }

    $is_owner = ($group['owner_id'] == $current_user_id);
    $is_admin = $is_owner || ($membership['is_admin'] == 1);

    // 3. Fetch Member List
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.profile_pic, gm.is_admin, gm.joined_at 
        FROM group_members gm
        JOIN users u ON gm.user_id = u.id
        WHERE gm.group_id = ?
        ORDER BY gm.is_admin DESC, u.username ASC
    ");
    $stmt->execute([$group_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add role attribute to members
    foreach ($members as &$m) {
        if ($m['id'] == $group['owner_id']) {
            $m['role'] = 'owner';
        } elseif ($m['is_admin']) {
            $m['role'] = 'admin';
        } else {
            $m['role'] = 'member';
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'settings' => $group,
        'members' => $members,
        'user_role' => $is_owner ? 'owner' : ($is_admin ? 'admin' : 'member'),
        'is_admin' => $is_admin
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
