<?php
// 系统配置
define('USER_DATA_FILE', __DIR__ . '/users.json');
define('UPLOAD_BASE_DIR', __DIR__ . '/uploads');
define('SHARES_FILE', __DIR__ . '/shares.json');

// 确保基础目录存在
if (!file_exists(UPLOAD_BASE_DIR)) {
    mkdir(UPLOAD_BASE_DIR, 0777, true);
}
if (!file_exists(SHARES_FILE)) {
    file_put_contents(SHARES_FILE, '{}');
}
if (!file_exists(USER_DATA_FILE)) {
    $defaultHash = password_hash('admin', PASSWORD_DEFAULT);
    file_put_contents(USER_DATA_FILE, json_encode(['admin' => $defaultHash]));
}

// ---------- 自动登录配置 ----------
ini_set('session.cookie_lifetime', 30 * 24 * 3600);
ini_set('session.gc_maxlifetime', 30 * 24 * 3600);

if (!session_id()) {
    session_save_path(sys_get_temp_dir());
    session_start();
}

// ---------- 辅助函数 ----------
function formatSize($bytes) {
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B','KB','MB','GB','TB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function generateToken() {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(16));
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        return bin2hex(openssl_random_pseudo_bytes(16));
    } else {
        return md5(uniqid(mt_rand(), true));
    }
}

function getMimeType($file) {
    if (function_exists('mime_content_type')) {
        return mime_content_type($file);
    } elseif (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mime;
    }
    return 'application/octet-stream';
}

function getCurrentUser() {
    return $_SESSION['username'] ?? null;
}

function getUserUploadDir($username = null) {
    if ($username === null) $username = getCurrentUser();
    if (!$username) return UPLOAD_BASE_DIR;
    $dir = UPLOAD_BASE_DIR . '/' . $username;
    if (!file_exists($dir)) mkdir($dir, 0777, true);
    return $dir;
}

// ---------- 计算用户目录大小 ----------
function getUserUsedSpace($username = null) {
    if ($username === null) $username = getCurrentUser();
    if (!$username) return 0;
    $dir = getUserUploadDir($username);
    if (!is_dir($dir)) return 0;
    $size = 0;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($files as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    return $size;
}

// ---------- 生成上传目录的 HTTP 基础 URL ----------
$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || $_SERVER['SERVER_PORT'] == 443 
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) 
    ? 'https://' : 'http://';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
define('UPLOAD_BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $basePath . '/uploads/');
?>