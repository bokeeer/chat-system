<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (!$group_id || !$name) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing data']);
    exit;
}

try {
    // Check if user is admin or owner
    $stmt = $pdo->prepare("
        SELECT gm.is_admin, g.owner_id 
        FROM group_members gm
        JOIN groups g ON g.id = gm.group_id
        WHERE gm.group_id = ? AND gm.user_id = ?
    ");
    $stmt->execute([$group_id, $user_id]);
    $res = $stmt->fetch();

    if (!$res || ($res['is_admin'] == 0 && $res['owner_id'] != $user_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }

    $profile_pic = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('grp_', true) . '.' . $ext;
        $target = 'uploads/' . $filename;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
            $profile_pic = $target;
        }
    }

    if ($profile_pic) {
        $upd = $pdo->prepare("UPDATE groups SET name = ?, description = ?, profile_pic = ? WHERE id = ?");
        $upd->execute([$name, $description, $profile_pic, $group_id]);
    } else {
        $upd = $pdo->prepare("UPDATE groups SET name = ?, description = ? WHERE id = ?");
        $upd->execute([$name, $description, $group_id]);
    }

    echo json_encode([
        'status' => 'Success',
        'profile_pic' => $profile_pic
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
