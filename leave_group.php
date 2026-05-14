<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;

if ($group_id <= 0) {
    http_response_code(400);
    echo "Invalid group_id";
    exit;
}

try {
    $chk = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
    $chk->execute([$group_id, $user_id]);
    if (!$chk->fetchColumn()) {
        http_response_code(403);
        echo "Not a member";
        exit;
    }

    $del = $pdo->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $del->execute([$group_id, $user_id]);

    $count = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ?");
    $count->execute([$group_id]);
    $remaining = (int)$count->fetchColumn();
    if ($remaining === 0) {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM group_messages WHERE group_id = ?")->execute([$group_id]);
        $pdo->prepare("DELETE FROM groups WHERE id = ?")->execute([$group_id]);
        $pdo->commit();
    }

    echo "Success";
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
}
?> 
