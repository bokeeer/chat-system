<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT g.id, g.name, g.owner_id, g.created_at, g.is_channel, g.channel_type, g.description, g.profile_pic, g.join_token, gm.last_read_id
        FROM `groups` g
        INNER JOIN group_members gm ON gm.group_id = g.id
        WHERE gm.user_id = ?
        ORDER BY g.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lastStmt = $pdo->prepare("
        SELECT m.id as last_msg_id, m.message, m.attachment, m.created_at, u.username
        FROM group_messages m
        JOIN users u ON u.id = m.user_id
        WHERE m.group_id = ?
        ORDER BY m.id DESC
        LIMIT 1
    ");

    $unreadStmt = $pdo->prepare("
        SELECT COUNT(*) FROM group_messages WHERE group_id = ? AND id > ? AND user_id != ?
    ");

    $result = [];
    foreach ($groups as $g) {
        $last_message = '';
        $last_time = '';
        $unread_count = 0;

        $lastStmt->execute([$g['id']]);
        if ($row = $lastStmt->fetch(PDO::FETCH_ASSOC)) {
            $msgPreview = $row['message'];
            if (!$msgPreview && $row['attachment']) {
                $isImg = preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $row['attachment']);
                $msgPreview = $isImg ? '[Image]' : '[File]';
            }
            $last_message = $row['username'] . ': ' . $msgPreview;
            $last_time = $row['created_at'];

            $unreadStmt->execute([$g['id'], $g['last_read_id'], $user_id]);
            $unread_count = (int) $unreadStmt->fetchColumn();
        }
        $result[] = [
            'id' => (int) $g['id'],
            'name' => $g['name'],
            'owner_id' => (int) $g['owner_id'],
            'is_channel' => (int) $g['is_channel'],
            'channel_type' => $g['channel_type'],
            'description' => $g['description'],
            'profile_pic' => $g['profile_pic'],
            'join_token' => $g['join_token'],
            'last_message' => $last_message,
            'last_time' => $last_time,
            'unread_count' => $unread_count
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($result);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>