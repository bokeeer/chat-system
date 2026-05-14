<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$me = (int)$_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// GET: list accepted friends
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sql = "
        SELECT u.id, u.username, u.bio, u.profile_pic
        FROM friends f
        JOIN users u ON (
            CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END = u.id
        )
        WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'
    ";
    
    if ($search !== '') {
        $sql .= " AND u.username LIKE ? ";
        $sql .= " ORDER BY u.username ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$me, $me, $me, "%$search%"]);
    } else {
        $sql .= " ORDER BY u.username ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$me, $me, $me]);
    }
    respond($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// GET: list pending incoming requests (others sent to me)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'pending') {
    $stmt = $pdo->prepare("
        SELECT f.id as request_id, u.id, u.username, u.profile_pic, f.created_at
        FROM friends f
        JOIN users u ON f.user_id = u.id
        WHERE f.friend_id = ? AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$me]);
    respond($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// GET: list pending outgoing requests (I sent to others)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'sent') {
    $stmt = $pdo->prepare("
        SELECT f.id as request_id, u.id, u.username, u.profile_pic
        FROM friends f
        JOIN users u ON f.friend_id = u.id
        WHERE f.user_id = ? AND f.status = 'pending'
    ");
    $stmt->execute([$me]);
    respond($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// POST: send friend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
    $friend_id = (int)($_POST['friend_id'] ?? 0);
    if (!$friend_id || $friend_id === $me) respond(['error' => 'Invalid user'], 400);

    // Check if already exists in either direction
    $check = $pdo->prepare("
        SELECT id, status FROM friends 
        WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
    ");
    $check->execute([$me, $friend_id, $friend_id, $me]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        respond(['error' => 'Already exists', 'status' => $existing['status']], 409);
    }

    $ins = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
    $ins->execute([$me, $friend_id]);
    respond(['status' => 'ok', 'message' => 'Friend request sent']);
}

// POST: accept friend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'accept') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    // Only accept if I am the friend_id
    $upd = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND friend_id = ?");
    $upd->execute([$request_id, $me]);
    if ($upd->rowCount() === 0) respond(['error' => 'Not found or not allowed'], 404);
    respond(['status' => 'ok', 'message' => 'Friend request accepted']);
}

// POST: reject / cancel / remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'reject' || $action === 'cancel' || $action === 'remove' || $action === 'unfriend')) {
    $friend_id = (int)($_POST['friend_id'] ?? 0);
    $request_id = (int)($_POST['request_id'] ?? 0);
    if ($request_id) {
        $del = $pdo->prepare("DELETE FROM friends WHERE id = ? AND (user_id = ? OR friend_id = ?)");
        $del->execute([$request_id, $me, $me]);
    } elseif ($friend_id) {
        $del = $pdo->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $del->execute([$me, $friend_id, $friend_id, $me]);
    } else {
        respond(['error' => 'friend_id or request_id required'], 400);
    }
    respond(['status' => 'ok']);
}

// GET: get friendship status with a specific user
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'status') {
    $friend_id = (int)($_GET['friend_id'] ?? 0);
    if (!$friend_id) respond(['status' => 'none']);
    $check = $pdo->prepare("
        SELECT id, status, user_id FROM friends 
        WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
    ");
    $check->execute([$me, $friend_id, $friend_id, $me]);
    $row = $check->fetch(PDO::FETCH_ASSOC);
    if (!$row) respond(['status' => 'none']);
    $direction = ($row['user_id'] == $me) ? 'sent' : 'received';
    respond(['status' => $row['status'], 'direction' => $direction, 'request_id' => $row['id']]);
}

// GET: count pending requests  
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'count') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE friend_id = ? AND status = 'pending'");
    $stmt->execute([$me]);
    respond(['count' => (int)$stmt->fetchColumn()]);
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
exit;
?>
