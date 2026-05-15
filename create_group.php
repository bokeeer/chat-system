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

$owner_id = (int) $_SESSION['user_id'];
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$is_channel = isset($_POST['is_channel']) ? (int) $_POST['is_channel'] : 0;
$channel_type = isset($_POST['channel_type']) ? $_POST['channel_type'] : 'public';
$member_ids_raw = isset($_POST['member_ids']) ? trim($_POST['member_ids']) : '';

if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Name required']);
    exit;
}

$profile_pic = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0755, true);

    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename = 'group_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
        $profile_pic = 'uploads/avatars/' . $filename;
    }
}

$join_token = null;
if ($is_channel || $channel_type === 'private') {
    $join_token = bin2hex(random_bytes(16));
}

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO `groups` (name, owner_id, is_channel, channel_type, description, profile_pic, join_token) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $owner_id, $is_channel, $channel_type, $description, $profile_pic, $join_token]);
    $group_id = (int) $pdo->lastInsertId();

    // Add owner as admin member
    $memIns = $pdo->prepare("INSERT INTO group_members (group_id, user_id, is_admin) VALUES (?, ?, ?)");
    $memIns->execute([$group_id, $owner_id, 1]);

    // Add initial members if provided (mostly for private groups)
    if ($member_ids_raw !== '') {
        $members = array_unique(array_filter(array_map('intval', explode(',', $member_ids_raw))));
        foreach ($members as $uid) {
            if ($uid > 0 && $uid !== $owner_id) {
                $memIns->execute([$group_id, $uid, 0]);
            }
        }
    }

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'Success',
        'group_id' => $group_id,
        'join_token' => $join_token,
        'is_channel' => $is_channel
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => "Database error: " . $e->getMessage()]);
}
?>