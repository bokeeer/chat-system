<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

$current_user_id = $_SESSION['user_id'];

try {

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $results = [];

    // Search Users
    if ($search) {
        $stmt = $pdo->prepare("SELECT id, username, bio, profile_pic, is_private FROM users WHERE id != ? AND username LIKE ? ORDER BY username ASC");
        $stmt->execute([$current_user_id, "%$search%"]);
    } else {
        $stmt = $pdo->prepare("SELECT id, username, bio, profile_pic, is_private FROM users WHERE id != ? ORDER BY username ASC");
        $stmt->execute([$current_user_id]);
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        $u['item_type'] = 'user';
        $results[] = $u;
    }

    // Search Public Channels (only if searching)
    if ($search) {
        $stmt = $pdo->prepare("SELECT id, name as username, description as bio, profile_pic, 1 as is_channel, 0 as is_private FROM groups WHERE is_channel = 1 AND channel_type = 'public' AND name LIKE ?");
        $stmt->execute(["%$search%"]);
        $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($channels as $c) {
            $c['item_type'] = 'channel';
            $results[] = $c;
        }
    }

    $augmented_results = [];

    foreach ($results as $item) {
        if ($item['item_type'] === 'user') {
            $other_user_id = $item['id'];
            
            $msgStmt = $pdo->prepare("
                SELECT message, attachment, created_at, user_id 
                FROM messages 
                WHERE 
                    (user_id = ? AND receiver_id = ?) 
                    OR 
                    (user_id = ? AND receiver_id = ?)
                ORDER BY id DESC LIMIT 1
            ");
            $msgStmt->execute([$current_user_id, $other_user_id, $other_user_id, $current_user_id]);
            $lastMsgRow = $msgStmt->fetch(PDO::FETCH_ASSOC);

            // ... (unread check)
            $unreadStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM messages 
                WHERE user_id = ? AND receiver_id = ? AND is_read = 0
            ");
            $unreadStmt->execute([$other_user_id, $current_user_id]);
            $unreadCount = $unreadStmt->fetchColumn();

            // ... (Friendship/History check)
            $stmtFriend = $pdo->prepare("SELECT 1 FROM friends WHERE status = 'accepted' AND ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))");
            $stmtFriend->execute([$current_user_id, $other_user_id, $other_user_id, $current_user_id]);
            $is_friend = (bool)$stmtFriend->fetchColumn();

            $stmtHistory = $pdo->prepare("SELECT 1 FROM messages WHERE (user_id = ? AND receiver_id = ?) OR (user_id = ? AND receiver_id = ?) LIMIT 1");
            $stmtHistory->execute([$current_user_id, $other_user_id, $other_user_id, $current_user_id]);
            $has_history = (bool)$stmtHistory->fetchColumn();

            if (empty($search) && !$is_friend && !$has_history) {
                continue;
            }

            $can_message = (bool)(!$item['is_private'] || $is_friend || $has_history);

            $msgPreview = $lastMsgRow ? $lastMsgRow['message'] : '';
            if (!$msgPreview && $lastMsgRow && $lastMsgRow['attachment']) {
                $isImg = preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $lastMsgRow['attachment']);
                $msgPreview = $isImg ? '[Image]' : '[File]';
            }

            $item['last_message'] = $msgPreview;
            $item['last_time'] = $lastMsgRow ? $lastMsgRow['created_at'] : '';
            $item['unread_count'] = $unreadCount;
            $item['can_message'] = $can_message;
        } else {
            // Channel search result
            $item['last_message'] = $item['bio'] ?: 'Public Channel';
            $item['last_time'] = '';
            $item['unread_count'] = 0;
            $item['can_message'] = true;
        }
        
        $augmented_results[] = $item;
    }

    header('Content-Type: application/json');
    echo json_encode($augmented_results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
