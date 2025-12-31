<?php
function db_path() {
    return __DIR__ . '/../db.json';
}

function read_db() {
    $path = db_path();
    if (!file_exists($path)) {
        file_put_contents($path, json_encode(['users'=>[], 'receipts'=>[]], JSON_PRETTY_PRINT));
    }
    $json = file_get_contents($path);
    return json_decode($json, true);
}

function write_db($data) {
    $path = db_path();
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

function find_user_by_device($device) {
    $db = read_db();
    foreach ($db['users'] as $u) {
        if (isset($u['device']) && $u['device'] === $device) return $u;
    }
    return null;
}

function find_user_by_email($email) {
    $db = read_db();
    foreach ($db['users'] as $u) {
        if (isset($u['email']) && strtolower($u['email']) === strtolower($email)) return $u;
    }
    return null;
}

function add_or_verify_user($email, $device, $ip, $strict = false) {
    // Backwards-compat helper: if a user exists for device and email matches, OK; otherwise error.
    $existing = find_user_by_device($device);
    if ($existing) {
        if (strtolower($existing['email']) !== strtolower($email)) {
            if ($strict) {
                return 'This device/IP already has a different email registered.';
            }
            // On service operations, allow different email from same device
        }
        return true;
    }
    // If email already exists, ensure it belongs to same device (legacy behavior)
    $emailOwner = find_user_by_email($email);
    if ($emailOwner && isset($emailOwner['device']) && $emailOwner['device'] !== $device) {
        if ($strict) {
            return 'This email is already registered from another device/IP.';
        }
        // On service operations, allow this check to pass
    }
    // Otherwise success (caller may create user record separately)
    return true;
}

function create_user($email, $password, $device, $ip) {
    $db = read_db();
    if (find_user_by_email($email)) return 'Email already registered.';
    if (find_user_by_device($device)) return 'This device/IP already has a different email registered.';

    $user = [
        'id' => uniqid('u_'),
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'device' => $device,
        'ip' => $ip,
        'created_at' => date('c')
    ];
    $db['users'][] = $user;
    write_db($db);
    return $user;
}

function verify_user_with_device_check($email, $device) {
    $user = find_user_by_email($email);
    if (!$user) return false;
    if (isset($user['device']) && $user['device'] !== $device) {
        return false; // Device mismatch on login
    }
    return $user;
}

function verify_user_credentials($email, $password) {
    $user = find_user_by_email($email);
    if (!$user) return false;
    if (!isset($user['password_hash'])) return false;
    if (password_verify($password, $user['password_hash'])) return $user;
    return false;
}

function add_receipt($receipt) {
    $db = read_db();
    $db['receipts'][] = $receipt;
    write_db($db);
}

function find_receipt($id) {
    $db = read_db();
    foreach ($db['receipts'] as $r) {
        if ($r['id'] === $id) return $r;
    }
    return null;
}
