<?php
session_start();
require 'db.php';


header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    $group_id = isset($_GET['group_id']) && !empty($_GET['group_id']) ? (int)$_GET['group_id'] : null;
    $contact_id = isset($_GET['contact_id']) && !empty($_GET['contact_id']) ? (int)$_GET['contact_id'] : null;

    if ($group_id) {
        $chk = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
        $chk->execute([$group_id, $user_id]);
        if (!$chk->fetchColumn()) {
            http_response_code(403);
            echo json_encode([]);
            exit;
        }

        $whereClause = "gm.group_id = :group_id";
        if ($last_id > 0) {
            $whereClause .= " AND gm.id > :last_id";
        }

        $stmt = $pdo->prepare("
            SELECT gm.id, gm.message, gm.attachment, gm.created_at, u.username, u.id as sender_id, u.profile_pic,
                   CASE WHEN gm.user_id = :user_id THEN 1 ELSE 0 END as is_self
            FROM group_messages gm
            JOIN users u ON gm.user_id = u.id
            WHERE $whereClause
            ORDER BY gm.created_at ASC
        ");
        $params = [':group_id' => $group_id, ':user_id' => $user_id];
        if ($last_id > 0) $params[':last_id'] = $last_id;
        $stmt->execute($params);
    } elseif ($contact_id) {
   
        $whereClause = "
            (
                (m.user_id = :user_id AND m.receiver_id = :contact_id) 
                OR 
                (m.user_id = :contact_id AND m.receiver_id = :user_id)
            )
        ";
        
        if ($last_id > 0) {
            $whereClause .= " AND m.id > :last_id";
        }

        $stmt = $pdo->prepare("
            SELECT m.id, m.message, m.attachment, m.created_at, u.username, u.id as sender_id, u.profile_pic,
            CASE WHEN m.user_id = :user_id THEN 1 ELSE 0 END as is_self
            FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE $whereClause
            ORDER BY m.created_at ASC
        ");

        $params = [':user_id' => $user_id, ':contact_id' => $contact_id];
        if ($last_id > 0) $params[':last_id'] = $last_id;
        
        $stmt->execute($params);

    } else {
        
        $whereClause = "m.receiver_id IS NULL";
        
        if ($last_id > 0) {
            $whereClause .= " AND m.id > :last_id";
        } else {
            
             $whereClause .= ""; 
        }

        if ($last_id > 0) {
             $stmt = $pdo->prepare("
                SELECT m.id, m.message, m.attachment, m.created_at, u.username, u.id as sender_id, u.profile_pic,
                CASE WHEN m.user_id = :user_id THEN 1 ELSE 0 END as is_self
                FROM messages m 
                JOIN users u ON m.user_id = u.id 
                WHERE $whereClause
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([':user_id' => $user_id, ':last_id' => $last_id]);
        } else {
            
            $stmt = $pdo->prepare("
                SELECT * FROM (
                    SELECT m.id, m.message, m.attachment, m.created_at, u.username, u.id as sender_id, u.profile_pic,
                    CASE WHEN m.user_id = :user_id THEN 1 ELSE 0 END as is_self
                    FROM messages m 
                    JOIN users u ON m.user_id = u.id 
                    WHERE m.receiver_id IS NULL
                    ORDER BY m.created_at DESC LIMIT 50
                ) as sub ORDER BY created_at ASC
            ");
            $stmt->execute([':user_id' => $user_id]);
        }
    }
    
    $messages = $stmt->fetchAll();

    header('Content-Type: application/json');
    echo json_encode($messages);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

