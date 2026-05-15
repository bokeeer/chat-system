<?php
require 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function j($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function segs()
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = array_values(array_filter(explode('/', $path)));
    $idx = array_search('api.php', $parts);
    if ($idx !== false) {
        return array_slice($parts, $idx + 1);
    }
    if (isset($_GET['endpoint'])) {
        return [$_GET['endpoint']];
    }
    return [];
}

function get_int($arr, $key)
{
    if (!isset($arr[$key]))
        return null;
    if ($arr[$key] === '')
        return null;
    return filter_var($arr[$key], FILTER_VALIDATE_INT) !== false ? (int) $arr[$key] : null;
}

function get_str($arr, $key, $max = 1000)
{
    if (!isset($arr[$key]))
        return null;
    $s = trim($arr[$key]);
    if ($s === '')
        return null;
    if (mb_strlen($s) > $max)
        $s = mb_substr($s, 0, $max);
    return $s;
}

$segs = segs();
$method = $_SERVER['REQUEST_METHOD'];
$first = $segs[0] ?? '';
$second = $segs[1] ?? '';

try {
    if ($first === 'health' || $first === '') {
        j(['status' => 'ok']);
    }

    if ($first === 'migrate') {
        try {
            // Helper to add column if it doesn't exist
            $addColumn = function($table, $col, $type) use ($pdo) {
                $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
                if (!$stmt->fetch()) {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $type");
                }
            };

            $addColumn('users', 'profile_pic', 'VARCHAR(255) DEFAULT NULL');
            $addColumn('users', 'bio', 'TEXT DEFAULT NULL');
            $addColumn('users', 'is_private', 'TINYINT(1) DEFAULT 0');
            $addColumn('messages', 'attachment', 'VARCHAR(255) DEFAULT NULL');
            $addColumn('groups', 'is_channel', 'TINYINT(1) DEFAULT 0');
            $addColumn('groups', 'channel_type', "VARCHAR(20) DEFAULT 'public'");
            $addColumn('groups', 'description', 'TEXT DEFAULT NULL');
            $addColumn('groups', 'profile_pic', 'VARCHAR(255) DEFAULT NULL');
            $addColumn('groups', 'join_token', 'VARCHAR(64) DEFAULT NULL');
            $addColumn('groups', 'view_only', 'TINYINT(1) DEFAULT 0');
            $addColumn('group_members', 'last_read_id', 'INT DEFAULT 0');
            $addColumn('group_messages', 'attachment', 'VARCHAR(255) DEFAULT NULL');

            $pdo->exec("CREATE TABLE IF NOT EXISTS friends (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                friend_id INT NOT NULL,
                status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (friend_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_friendship (user_id, friend_id)
            )");

            j(['status' => 'migration successful']);
        } catch (Exception $e) {
            j(['error' => 'migration failed', 'detail' => $e->getMessage()], 500);
        }
    }

    if ($first === 'users') {
        if ($method === 'GET') {
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $current_id = get_int($_GET, 'user_id');
            if ($search !== '') {
                if ($current_id) {
                    $stmt = $pdo->prepare("SELECT id, username, profile_pic, bio, created_at FROM users WHERE id != ? AND username LIKE ? ORDER BY username ASC");
                    $stmt->execute([$current_id, "%$search%"]);
                } else {
                    $stmt = $pdo->prepare("SELECT id, username, profile_pic, bio, created_at FROM users WHERE username LIKE ? ORDER BY username ASC");
                    $stmt->execute(["%$search%"]);
                }
            } else {
                if ($current_id) {
                    $stmt = $pdo->prepare("SELECT id, username, profile_pic, bio, created_at FROM users WHERE id != ? ORDER BY username ASC");
                    $stmt->execute([$current_id]);
                } else {
                    $stmt = $pdo->query("SELECT id, username, profile_pic, bio, created_at FROM users ORDER BY username ASC");
                }
            }
            $users = $stmt->fetchAll();
            if ($current_id) {
                $aug = [];
                foreach ($users as $user) {
                    $other_id = (int) $user['id'];
                    $msgStmt = $pdo->prepare("SELECT message, created_at, user_id FROM messages WHERE (user_id = ? AND receiver_id = ?) OR (user_id = ? AND receiver_id = ?) ORDER BY id DESC LIMIT 1");
                    $msgStmt->execute([$current_id, $other_id, $other_id, $current_id]);
                    $lastMsg = $msgStmt->fetch(PDO::FETCH_ASSOC);
                    $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ? AND receiver_id = ? AND is_read = 0");
                    $unreadStmt->execute([$other_id, $current_id]);
                    $unreadCount = (int) $unreadStmt->fetchColumn();

                    // Check privacy and messaging permission
                    $stmtPriv = $pdo->prepare("SELECT is_private FROM users WHERE id = ?");
                    $stmtPriv->execute([$other_id]);
                    $isPrivate = (int) $stmtPriv->fetchColumn();

                    $stmtFriend = $pdo->prepare("SELECT 1 FROM friends WHERE status = 'accepted' AND ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))");
                    $stmtFriend->execute([$current_id, $other_id, $other_id, $current_id]);
                    $isFriend = (bool) $stmtFriend->fetchColumn();

                    $stmtHistory = $pdo->prepare("SELECT 1 FROM messages WHERE (user_id = ? AND receiver_id = ?) OR (user_id = ? AND receiver_id = ?) LIMIT 1");
                    $stmtHistory->execute([$current_id, $other_id, $other_id, $current_id]);
                    $hasHistory = (bool) $stmtHistory->fetchColumn();

                    if ($search === '' && !$isFriend && !$hasHistory) {
                        continue;
                    }

                    $user['is_private'] = $isPrivate;
                    $user['can_message'] = (bool) (!$isPrivate || $isFriend || $hasHistory);
                    $user['last_message'] = $lastMsg ? $lastMsg['message'] : '';
                    $user['last_time'] = $lastMsg ? $lastMsg['created_at'] : '';
                    $user['unread_count'] = $unreadCount;
                    $aug[] = $user;
                }
                j($aug);
            } else {
                j($users);
            }
        }
        if ($method === 'POST') {
            $username = get_str($_POST, 'username', 50);
            $password = get_str($_POST, 'password', 255);
            if (!$username || !$password)
                j(['error' => 'username and password required'], 400);
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->rowCount() > 0)
                j(['error' => 'username exists'], 409);
            $ins = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $ins->execute([$username, $password]);
            j(['id' => (int) $pdo->lastInsertId(), 'username' => $username], 201);
        }
        j(['error' => 'method not allowed'], 405);
    }

    if ($first === 'login' && $method === 'POST') {
        $username = get_str($_POST, 'username', 50);
        $password = get_str($_POST, 'password', 255);
        if (!$username || !$password)
            j(['error' => 'username and password required'], 400);
        $stmt = $pdo->prepare("SELECT id, username, password, profile_pic, bio FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user) {
            $stored = $user['password'];
            $is_hash = (substr($stored, 0, 4) === '$2y$' || substr($stored, 0, 7) === '$argon2');
            if ($is_hash) {
                if (password_verify($password, $stored)) {
                    j([
                        'id' => (int) $user['id'], 
                        'username' => $user['username'],
                        'profile_pic' => $user['profile_pic'],
                        'bio' => $user['bio']
                    ]);
                }
            } else {
                if ($password === $stored) {
                    j([
                        'id' => (int) $user['id'], 
                        'username' => $user['username'],
                        'profile_pic' => $user['profile_pic'],
                        'bio' => $user['bio']
                    ]);
                }
            }
        }
        j(['error' => 'invalid credentials'], 401);
    }

    if ($first === 'messages') {
        if ($method === 'GET') {
            $user_id = get_int($_GET, 'user_id');
            $last_id = get_int($_GET, 'last_id') ?? 0;
            $group_id = get_int($_GET, 'group_id');
            $contact_id = get_int($_GET, 'contact_id');
            if (!$user_id)
                j(['error' => 'user_id required'], 400);
            if ($group_id) {
                $chk = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
                $chk->execute([$group_id, $user_id]);
                if (!$chk->fetchColumn())
                    j([], 200);
                $where = "gm.group_id = :gid";
                if ($last_id > 0)
                    $where .= " AND gm.id > :lid";
                $sql = "SELECT gm.id, gm.message, gm.created_at, u.username, u.profile_pic, gm.user_id as sender_id, CASE WHEN gm.user_id = :uid THEN 1 ELSE 0 END as is_self
                        FROM group_messages gm JOIN users u ON gm.user_id = u.id
                        WHERE $where ORDER BY gm.created_at ASC";
                $stmt = $pdo->prepare($sql);
                $params = [':gid' => $group_id, ':uid' => $user_id];
                if ($last_id > 0)
                    $params[':lid'] = $last_id;
                $stmt->execute($params);
            } elseif ($contact_id) {
                $where = "(m.user_id = :uid AND m.receiver_id = :cid) OR (m.user_id = :cid AND m.receiver_id = :uid)";
                if ($last_id > 0)
                    $where .= " AND m.id > :lid";
                $sql = "SELECT m.id, m.message, m.created_at, u.username, u.profile_pic, m.user_id as sender_id, CASE WHEN m.user_id = :uid THEN 1 ELSE 0 END as is_self
                        FROM messages m JOIN users u ON m.user_id = u.id WHERE $where ORDER BY m.created_at ASC";
                $stmt = $pdo->prepare($sql);
                $params = [':uid' => $user_id, ':cid' => $contact_id];
                if ($last_id > 0)
                    $params[':lid'] = $last_id;
                $stmt->execute($params);
            } else {
                if ($last_id > 0) {
                    $sql = "SELECT m.id, m.message, m.created_at, u.username, u.profile_pic, m.user_id as sender_id, CASE WHEN m.user_id = :uid THEN 1 ELSE 0 END as is_self
                            FROM messages m JOIN users u ON m.user_id = u.id 
                            WHERE m.receiver_id IS NULL AND m.id > :lid ORDER BY m.created_at ASC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':uid' => $user_id, ':lid' => $last_id]);
                } else {
                    $sql = "SELECT * FROM (
                                SELECT m.id, m.message, m.created_at, u.username, u.profile_pic, m.user_id as sender_id, CASE WHEN m.user_id = :uid THEN 1 ELSE 0 END as is_self
                                FROM messages m JOIN users u ON m.user_id = u.id 
                                WHERE m.receiver_id IS NULL ORDER BY m.created_at DESC LIMIT 50
                            ) as sub ORDER BY created_at ASC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':uid' => $user_id]);
                }
            }
            $messages = $stmt->fetchAll();
            j($messages);
        }
        if ($second === 'read' && $method === 'POST') {
            $user_id = get_int($_POST, 'user_id');
            $sender_id = get_int($_POST, 'sender_id');
            if (!$user_id || !$sender_id)
                j(['error' => 'user_id and sender_id required'], 400);
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND user_id = ? AND is_read = 0");
            $stmt->execute([$user_id, $sender_id]);
            j(['status' => 'ok']);
        }
        if ($method === 'POST') {
            $user_id = get_int($_POST, 'user_id');
            $message = get_str($_POST, 'message', 5000);
            $group_id = get_int($_POST, 'group_id');
            $receiver_id = get_int($_POST, 'receiver_id');
            if (!$user_id || !$message)
                j(['error' => 'user_id and message required'], 400);
            if ($group_id) {
                $chk = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
                $chk->execute([$group_id, $user_id]);
                if (!$chk->fetchColumn())
                    j(['error' => 'not a member'], 403);
                $ins = $pdo->prepare("INSERT INTO group_messages (group_id, user_id, message) VALUES (?, ?, ?)");
                $ins->execute([$group_id, $user_id, $message]);
            } else {
                // Privacy Check for DM
                if ($receiver_id) {
                    $stmt = $pdo->prepare("SELECT is_private FROM users WHERE id = ?");
                    $stmt->execute([$receiver_id]);
                    $receiver = $stmt->fetch();
                    if ($receiver && $receiver['is_private'] == 1) {
                        $friendCheck = $pdo->prepare("SELECT id FROM friends WHERE status = 'accepted' AND ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))");
                        $friendCheck->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
                        $isFriend = $friendCheck->fetch();

                        if (!$isFriend) {
                            $historyCheck = $pdo->prepare("SELECT id FROM messages WHERE user_id = ? AND receiver_id = ? LIMIT 1");
                            $historyCheck->execute([$receiver_id, $user_id]);
                            if (!$historyCheck->fetch()) {
                                j(['error' => 'this user is set the account on private'], 403);
                            }
                        }
                    }
                }
                $ins = $pdo->prepare("INSERT INTO messages (user_id, receiver_id, message) VALUES (?, ?, ?)");
                $ins->execute([$user_id, $receiver_id, $message]);
            }
            j(['status' => 'ok', 'id' => (int) $pdo->lastInsertId()], 201);
        }
        j(['error' => 'method not allowed'], 405);
    }

    if ($first === 'groups') {
        if ($method === 'GET') {
            $user_id = get_int($_GET, 'user_id');
            if (!$user_id)
                j(['error' => 'user_id required'], 400);
            $stmt = $pdo->prepare("SELECT g.id, g.name, g.owner_id, g.profile_pic, g.created_at
                                   FROM `groups` g
                                   JOIN group_members gm ON gm.group_id = g.id
                                   WHERE gm.user_id = ?
                                   ORDER BY g.created_at DESC");
            $stmt->execute([$user_id]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $lastStmt = $pdo->prepare("SELECT m.message, m.created_at, u.username
                                       FROM group_messages m
                                       JOIN users u ON u.id = m.user_id
                                       WHERE m.group_id = ?
                                       ORDER BY m.id DESC LIMIT 1");
            $out = [];
            foreach ($groups as $g) {
                $last = '';
                $time = '';
                $lastStmt->execute([$g['id']]);
                if ($row = $lastStmt->fetch(PDO::FETCH_ASSOC)) {
                    $last = $row['username'] . ': ' . $row['message'];
                    $time = $row['created_at'];
                }
                $out[] = [
                    'id' => (int) $g['id'], 
                    'name' => $g['name'], 
                    'owner_id' => (int) $g['owner_id'], 
                    'profile_pic' => $g['profile_pic'],
                    'last_message' => $last, 
                    'last_time' => $time
                ];
            }
            j($out);
        }
        if ($second === 'leave' && $method === 'POST') {
            $user_id = get_int($_POST, 'user_id');
            $group_id = get_int($_POST, 'group_id');
            if (!$user_id || !$group_id)
                j(['error' => 'user_id and group_id required'], 400);
            $chk = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id=? AND user_id=?");
            $chk->execute([$group_id, $user_id]);
            if (!$chk->fetchColumn())
                j(['error' => 'not a member'], 403);
            $pdo->prepare("DELETE FROM group_members WHERE group_id=? AND user_id=?")->execute([$group_id, $user_id]);
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id=?");
            $cnt->execute([$group_id]);
            if ((int) $cnt->fetchColumn() === 0) {
                $pdo->prepare("DELETE FROM group_messages WHERE group_id=?")->execute([$group_id]);
                $pdo->prepare("DELETE FROM `groups` WHERE id=?")->execute([$group_id]);
            }
            j(['status' => 'ok']);
        }
        if ($method === 'POST') {
            $owner_id = get_int($_POST, 'user_id');
            $name = get_str($_POST, 'name', 100);
            $member_ids = get_str($_POST, 'member_ids', 1000);
            if (!$owner_id || !$name)
                j(['error' => 'owner_id and name required'], 400);
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO `groups` (name, owner_id) VALUES (?, ?)")->execute([$name, $owner_id]);
            $gid = (int) $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO group_members (group_id, user_id, is_admin) VALUES (?, ?, 1)")->execute([$gid, $owner_id]);
            if ($member_ids) {
                $ids = array_unique(array_filter(array_map(function($x) { return (int) trim($x); }, explode(',', $member_ids))));
                $ins = $pdo->prepare("INSERT IGNORE INTO group_members (group_id, user_id, is_admin) VALUES (?, ?, 0)");
                foreach ($ids as $id) {
                    if ($id > 0 && $id !== $owner_id)
                        $ins->execute([$gid, $id]);
                }
            }
            $pdo->commit();
            j(['status' => 'ok', 'group_id' => $gid], 201);
        }
        j(['error' => 'method not allowed'], 405);
    }

    // ─── Friends API ─────────────────────────────────────
    if ($first === 'friends') {
        $user_id = get_int($_GET, 'user_id') ?? get_int($_POST, 'user_id');

        if ($method === 'GET' && $second === 'list') {
            if (!$user_id) j(['error' => 'user_id required'], 400);
            $search = get_str($_GET, 'search', 100);
            $sql = "SELECT u.id, u.username, u.bio, u.profile_pic
                    FROM friends f
                    JOIN users u ON (CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END = u.id)
                    WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'";
            if ($search) {
                $sql .= " AND u.username LIKE ? ORDER BY u.username ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $user_id, $user_id, "%$search%"]);
            } else {
                $sql .= " ORDER BY u.username ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $user_id, $user_id]);
            }
            j($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        if ($method === 'GET' && $second === 'pending') {
            if (!$user_id) j(['error' => 'user_id required'], 400);
            $stmt = $pdo->prepare("SELECT f.id as request_id, u.id, u.username, u.profile_pic, f.created_at
                                   FROM friends f JOIN users u ON f.user_id = u.id
                                   WHERE f.friend_id = ? AND f.status = 'pending'
                                   ORDER BY f.created_at DESC");
            $stmt->execute([$user_id]);
            j($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        if ($method === 'GET' && $second === 'count') {
            if (!$user_id) j(['error' => 'user_id required'], 400);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM friends WHERE friend_id = ? AND status = 'pending'");
            $stmt->execute([$user_id]);
            j(['count' => (int) $stmt->fetchColumn()]);
        }

        if ($method === 'GET' && $second === 'status') {
            if (!$user_id) j(['status' => 'none']);
            $friend_id = get_int($_GET, 'friend_id');
            if (!$friend_id) j(['status' => 'none']);
            $chk = $pdo->prepare("SELECT id, status, user_id FROM friends
                                  WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $chk->execute([$user_id, $friend_id, $friend_id, $user_id]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$row) j(['status' => 'none']);
            $direction = ($row['user_id'] == $user_id) ? 'sent' : 'received';
            j(['status' => $row['status'], 'direction' => $direction, 'request_id' => (int) $row['id']]);
        }

        if ($method === 'POST' && $second === 'send') {
            if (!$user_id) j(['error' => 'user_id required'], 400);
            $friend_id = get_int($_POST, 'friend_id');
            if (!$friend_id || $friend_id === $user_id) j(['error' => 'Invalid user'], 400);
            $chk = $pdo->prepare("SELECT id, status FROM friends
                                  WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
            $chk->execute([$user_id, $friend_id, $friend_id, $user_id]);
            if ($chk->fetch()) j(['error' => 'Already exists'], 409);
            $ins = $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
            $ins->execute([$user_id, $friend_id]);
            j(['status' => 'ok', 'message' => 'Friend request sent'], 201);
        }

        if ($method === 'POST' && $second === 'accept') {
            if (!$user_id) j(['error' => 'user_id required'], 400);
            $request_id = get_int($_POST, 'request_id');
            if (!$request_id) j(['error' => 'request_id required'], 400);
            $upd = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE id = ? AND friend_id = ?");
            $upd->execute([$request_id, $user_id]);
            if ($upd->rowCount() === 0) j(['error' => 'Not found'], 404);
            j(['status' => 'ok']);
        }

        if ($method === 'POST' && ($second === 'reject' || $second === 'cancel' || $second === 'unfriend')) {
            if (!$user_id) j(['error' => 'user_id required'], 400);
            $friend_id = get_int($_POST, 'friend_id');
            $request_id = get_int($_POST, 'request_id');
            if ($request_id) {
                $del = $pdo->prepare("DELETE FROM friends WHERE id = ? AND (user_id = ? OR friend_id = ?)");
                $del->execute([$request_id, $user_id, $user_id]);
            } elseif ($friend_id) {
                $del = $pdo->prepare("DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
                $del->execute([$user_id, $friend_id, $friend_id, $user_id]);
            } else {
                j(['error' => 'friend_id or request_id required'], 400);
            }
            j(['status' => 'ok']);
        }

        // Default GET for friends (same as list)
        if ($method === 'GET') {
            if (!$user_id) j(['error' => 'user_id required'], 400);
            $stmt = $pdo->prepare("SELECT u.id, u.username, u.bio, u.profile_pic
                                   FROM friends f
                                   JOIN users u ON (CASE WHEN f.user_id = ? THEN f.friend_id ELSE f.user_id END = u.id)
                                   WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'
                                   ORDER BY u.username ASC");
            $stmt->execute([$user_id, $user_id, $user_id]);
            j($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        j(['error' => 'method not allowed'], 405);
    }

    j(['error' => 'not found'], 404);
} catch (PDOException $e) {
    j(['error' => 'database error', 'detail' => $e->getMessage()], 500);
}
?>