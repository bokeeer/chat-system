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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$bio = trim($_POST['bio'] ?? '');
if (mb_strlen($bio) > 300) {
    $bio = mb_substr($bio, 0, 300);
}

$stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
$stmt->execute([$bio, $me]);

echo json_encode(['ok' => true, 'bio' => $bio]);
exit;
?>
