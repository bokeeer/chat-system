<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = trim($_POST['message'] ?? '');
    $user_id = $_SESSION['user_id'];
    $group_id = isset($_POST['group_id']) && !empty($_POST['group_id']) ? (int) $_POST['group_id'] : null;
    $receiver_id = isset($_POST['receiver_id']) && !empty($_POST['receiver_id']) ? $_POST['receiver_id'] : null;

    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('att_', true) . '.' . $ext;
        $target = 'uploads/' . $filename;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target)) {
            $attachment = $target;
        }
    }

    if (!empty($message) || $attachment) {
        try {
            if ($group_id) {
                // ... (existing group check)
                $chk = $pdo->prepare("
                    SELECT g.view_only, g.owner_id, gm.is_admin 
                    FROM `groups` g
                    JOIN group_members gm ON g.id = gm.group_id
                    WHERE g.id = ? AND gm.user_id = ?
                ");
                $chk->execute([$group_id, $user_id]);
                $groupInfo = $chk->fetch(PDO::FETCH_ASSOC);

                if (!$groupInfo) {
                    http_response_code(403);
                    echo "Not a group member";
                    exit;
                }

                $is_admin = ($groupInfo['owner_id'] == $user_id) || ($groupInfo['is_admin'] == 1);
                if ($groupInfo['view_only'] && !$is_admin) {
                    http_response_code(403);
                    echo "Only admins can send messages in this channel";
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO group_messages (group_id, user_id, message, attachment) VALUES (?, ?, ?, ?)");
                $stmt->execute([$group_id, $user_id, $message, $attachment]);
            } else {
                // Privacy Check for Direct Messages
                $stmt = $pdo->prepare("SELECT is_private FROM users WHERE id = ?");
                $stmt->execute([$receiver_id]);
                $receiver = $stmt->fetch();

                if ($receiver && $receiver['is_private'] == 1) {
                    // Check if they are friends
                    $friendCheck = $pdo->prepare("
                        SELECT id FROM friends 
                        WHERE status = 'accepted' 
                        AND (
                            (user_id = ? AND friend_id = ?) OR 
                            (user_id = ? AND friend_id = ?)
                        )
                    ");
                    $friendCheck->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
                    $isFriend = $friendCheck->fetch();

                    if (!$isFriend) {
                        // Check if the receiver has chatted with the sender first
                        $historyCheck = $pdo->prepare("
                            SELECT id FROM messages 
                            WHERE user_id = ? AND receiver_id = ? 
                            LIMIT 1
                        ");
                        $historyCheck->execute([$receiver_id, $user_id]);
                        if (!$historyCheck->fetch()) {
                            http_response_code(403);
                            echo "this user is set the account on private";
                            exit;
                        }
                    }
                }

                $stmt = $pdo->prepare("INSERT INTO messages (user_id, receiver_id, message, attachment) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $receiver_id, $message, $attachment]);
            }
            echo "Success";
        } catch (PDOException $e) {
            http_response_code(500);
            echo "Database error: " . $e->getMessage();
        }
    } else {
        http_response_code(400);
        echo "Empty message";
    }
}
?>