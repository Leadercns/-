<?php
require_once 'config.php';

// 登录检查
function isLoggedIn() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true && isset($_SESSION['username']);
}

// 登录处理（从 users.json 验证）
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $users = json_decode(file_get_contents(USER_DATA_FILE), true);
    if (isset($users[$username]) && password_verify($password, $users[$username])) {
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $username;
        $userDir = getUserUploadDir($username);
        if (!file_exists($userDir)) mkdir($userDir, 0777, true);
        header('Location: index.php');
        exit;
    } else {
        $loginError = '用户名或密码错误';
        include 'login.php';
        exit;
    }
}

// 登出
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!isLoggedIn()) {
    include 'login.php';
    exit;
}

$currentUser = getCurrentUser();
$userUploadDir = getUserUploadDir($currentUser);

// ---------- 安全路径函数 ----------
function getCurrentPath() {
    global $userUploadDir;
    $base = $userUploadDir;
    if (!file_exists($base)) mkdir($base, 0777, true);
    $path = isset($_GET['path']) ? $_GET['path'] : '';
    $path = urldecode($path);
    $full = realpath($base . '/' . ltrim($path, '/'));
    if ($full === false || strpos($full, $base) !== 0) {
        return $base;
    }
    return $full;
}

function getRelativePath($path) {
    global $userUploadDir;
    $base = $userUploadDir;
    $real = realpath($path);
    if ($real && strpos($real, $base) === 0) {
        return substr($real, strlen($base) + 1);
    }
    return '';
}

$currentPath = getCurrentPath();
$relativePath = getRelativePath($currentPath);

// ---------- 处理 POST ----------
$successMessage = $errorMessage = '';

// 创建文件夹
if (isset($_POST['create_folder'])) {
    $name = trim($_POST['folder_name']);
    if ($name) {
        $dir = $currentPath . '/' . $name;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true) ? $successMessage = '创建成功' : $errorMessage = '创建失败';
        } else {
            $errorMessage = '文件夹已存在';
        }
    } else {
        $errorMessage = '请输入名称';
    }
}

// 重命名文件夹
if (isset($_POST['rename_folder'])) {
    $old = $_POST['old_name'];
    $new = trim($_POST['new_name']);
    if ($new) {
        $oldPath = $currentPath . '/' . $old;
        $newPath = $currentPath . '/' . $new;
        if (is_dir($oldPath) && !file_exists($newPath)) {
            rename($oldPath, $newPath) ? $successMessage = '重命名成功' : $errorMessage = '重命名失败';
        } else {
            $errorMessage = '源不存在或目标已存在';
        }
    } else {
        $errorMessage = '请输入新名称';
    }
}

// 删除文件夹（递归）
if (isset($_POST['delete_folder'])) {
    $name = $_POST['folder_name'];
    $target = $currentPath . '/' . $name;
    if (is_dir($target)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($target) ? $successMessage = '删除成功' : $errorMessage = '删除失败';
    } else {
        $errorMessage = '文件夹不存在';
    }
}

// 重命名文件
if (isset($_POST['rename_file'])) {
    $old = $_POST['old_name'];
    $new = trim($_POST['new_name']);
    if ($new) {
        $oldPath = $currentPath . '/' . $old;
        $newPath = $currentPath . '/' . $new;
        if (is_file($oldPath) && !file_exists($newPath)) {
            rename($oldPath, $newPath) ? $successMessage = '重命名成功' : $errorMessage = '重命名失败';
        } else {
            $errorMessage = '源文件不存在或目标已存在';
        }
    } else {
        $errorMessage = '请输入新名称';
    }
}

// 删除文件
if (isset($_POST['delete_file'])) {
    $name = $_POST['file_name'];
    $target = $currentPath . '/' . $name;
    if (is_file($target) && unlink($target)) {
        $successMessage = '删除成功';
    } else {
        $errorMessage = '删除失败或文件不存在';
    }
}

// 移动文件（单个）
if (isset($_POST['move_file'])) {
    $fileName = $_POST['file_name'];
    $targetFolder = ltrim($_POST['target_folder'], '/');
    $src = $currentPath . '/' . $fileName;
    $dst = $userUploadDir . '/' . $targetFolder . '/' . $fileName;
    if (is_file($src) && !file_exists($dst)) {
        $dstDir = dirname($dst);
        if (!file_exists($dstDir)) mkdir($dstDir, 0777, true);
        rename($src, $dst) ? $successMessage = '移动成功' : $errorMessage = '移动失败';
    } else {
        $errorMessage = '源文件不存在或目标已存在';
    }
}

// 批量删除
if (isset($_POST['delete_selected_files'])) {
    $selected = $_POST['selected_files'] ?? [];
    $count = 0;
    foreach ($selected as $rel) {
        $full = $userUploadDir . '/' . $rel;
        if (is_file($full) && unlink($full)) $count++;
    }
    $successMessage = "成功删除 {$count} 个文件";
}

// 批量移动
if (isset($_POST['move_selected_files'])) {
    $selected = $_POST['selected_files'] ?? [];
    $targetFolder = ltrim($_POST['target_folder'], '/');
    $dstDir = $userUploadDir . '/' . $targetFolder;
    if (!file_exists($dstDir)) mkdir($dstDir, 0777, true);
    $count = 0;
    foreach ($selected as $rel) {
        $src = $userUploadDir . '/' . $rel;
        $dst = $dstDir . '/' . basename($rel);
        if (is_file($src) && !file_exists($dst) && rename($src, $dst)) $count++;
    }
    $successMessage = "成功移动 {$count} 个文件";
}

// 批量上传
if (isset($_FILES['files'])) {
    $uploaded = 0;
    foreach ($_FILES['files']['error'] as $key => $err) {
        if ($err === UPLOAD_ERR_OK) {
            $name = $_FILES['files']['name'][$key];
            $tmp = $_FILES['files']['tmp_name'][$key];
            $dest = $currentPath . '/' . $name;
            $counter = 1;
            while (file_exists($dest)) {
                $info = pathinfo($name);
                $dest = $currentPath . '/' . $info['filename'] . '_' . $counter . '.' . ($info['extension'] ?? '');
                $counter++;
            }
            if (move_uploaded_file($tmp, $dest)) $uploaded++;
        }
    }
    $_SESSION['successMessage'] = "成功上传 {$uploaded} 个文件";
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// ---------- AJAX 接口 ----------
// 生成分享链接
if (isset($_GET['action']) && $_GET['action'] === 'share' && isset($_GET['file'])) {
    $relFile = urldecode($_GET['file']);
    $fullFile = $userUploadDir . '/' . $relFile;
    if (!is_file($fullFile)) {
        die(json_encode(['error' => '文件不存在']));
    }
    $shares = json_decode(file_get_contents(SHARES_FILE), true);
    // 检查是否已有有效分享，若有则复用（先删除旧的再生成新？用户选择自定义有效期，我们重新生成）
    // 为简化，直接生成新 token（旧 token 将失效，但可保留在 shares.json 中，通过过期时间忽略）
    // 但为了撤销方便，如果已有有效分享，我们仍然可以生成新的，旧的自动失效（因为过期时间会更新）
    // 这里我们选择：删除旧的相同文件分享（如果存在），再添加新的
    foreach ($shares as $token => $data) {
        if ($data['file'] === $relFile && $data['user'] === $currentUser) {
            unset($shares[$token]);
        }
    }
    $token = generateToken();
    $expire = isset($_GET['expire']) ? time() + intval($_GET['expire']) : null;
    $shares[$token] = [
        'file' => $relFile,
        'expire' => $expire,
        'user' => $currentUser
    ];
    file_put_contents(SHARES_FILE, json_encode($shares));
    $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || $_SERVER['SERVER_PORT'] == 443 
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) 
        ? 'https://' : 'http://';
    $shareUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/share.php?token=' . $token;
    header('Content-Type: application/json');
    echo json_encode(['url' => $shareUrl, 'token' => $token]);
    exit;
}

// 取消分享
if (isset($_GET['action']) && $_GET['action'] === 'unshare' && isset($_GET['token'])) {
    $token = $_GET['token'];
    $shares = json_decode(file_get_contents(SHARES_FILE), true);
    if (isset($shares[$token]) && $shares[$token]['user'] === $currentUser) {
        unset($shares[$token]);
        file_put_contents(SHARES_FILE, json_encode($shares));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => '分享不存在或无权操作']);
    }
    exit;
}

// 转存分享文件
if (isset($_GET['action']) && $_GET['action'] === 'transfer' && isset($_GET['token'])) {
    $token = $_GET['token'];
    $shares = json_decode(file_get_contents(SHARES_FILE), true);
    if (!isset($shares[$token])) {
        die(json_encode(['success' => false, 'message' => '分享链接无效']));
    }
    $share = $shares[$token];
    $owner = $share['user'];
    $relFile = $share['file'];
    $srcFile = getUserUploadDir($owner) . '/' . $relFile;
    if (!is_file($srcFile)) {
        die(json_encode(['success' => false, 'message' => '源文件不存在']));
    }
    if (!isLoggedIn()) {
        die(json_encode(['success' => false, 'message' => '请先登录']));
    }
    $currentUser = getCurrentUser();
    if ($currentUser === $owner) {
        die(json_encode(['success' => false, 'message' => '这是您自己的文件，无需转存']));
    }
    $targetDir = getUserUploadDir($currentUser);
    $baseName = basename($relFile);
    $targetPath = $targetDir . '/' . $baseName;
    $counter = 1;
    $info = pathinfo($baseName);
    while (file_exists($targetPath)) {
        $newName = $info['filename'] . '_' . $counter . '.' . ($info['extension'] ?? '');
        $targetPath = $targetDir . '/' . $newName;
        $counter++;
    }
    if (copy($srcFile, $targetPath)) {
        die(json_encode(['success' => true, 'message' => '文件已成功转存到您的云盘根目录']));
    } else {
        die(json_encode(['success' => false, 'message' => '文件复制失败，请检查权限']));
    }
}

// 下载单个文件
if (isset($_GET['download_file'])) {
    $rel = $_GET['file'];
    $file = $userUploadDir . '/' . $rel;
    if (is_file($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    } else {
        $errorMessage = '文件不存在';
    }
}

// 批量下载（ZIP）
if (isset($_GET['download_selected_files'])) {
    $files = explode(',', $_GET['files']);
    if (!class_exists('ZipArchive')) {
        die('您的PHP环境未启用Zip扩展，无法批量下载。请逐个下载。');
    }
    $zip = new ZipArchive();
    $tmpZip = tempnam(sys_get_temp_dir(), 'cloud_') . '.zip';
    if ($zip->open($tmpZip, ZipArchive::CREATE) === true) {
        foreach ($files as $rel) {
            $full = $userUploadDir . '/' . $rel;
            if (is_file($full)) {
                $zip->addFile($full, basename($rel));
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="download_' . time() . '.zip"');
        header('Content-Length: ' . filesize($tmpZip));
        readfile($tmpZip);
        unlink($tmpZip);
        exit;
    } else {
        $errorMessage = '创建压缩包失败';
    }
}

// ---------- 获取文件列表 ----------
$folders = [];
$files = [];

// 加载当前用户的分享记录（用于判断文件是否已分享）
$shares = json_decode(file_get_contents(SHARES_FILE), true);
$shareMap = [];
foreach ($shares as $token => $data) {
    if ($data['user'] === $currentUser && ($data['expire'] === null || $data['expire'] > time())) {
        $shareMap[$data['file']] = $token;
    }
}

$dir = new DirectoryIterator($currentPath);
foreach ($dir as $item) {
    if ($item->isDot()) continue;
    $name = $item->getFilename();
    $rel = getRelativePath($item->getPathname());
    if ($item->isDir()) {
        $folders[] = ['name' => $name, 'path' => $rel, 'time' => $item->getMTime()];
    } else {
        $files[] = [
            'name' => $name,
            'path' => $rel,
            'time' => $item->getMTime(),
            'size' => $item->getSize(),
            'mime' => getMimeType($item->getPathname()),
            'share_token' => $shareMap[$rel] ?? null, // 有效分享的token
        ];
    }
}

usort($folders, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
usort($files, function($a, $b) { return strcasecmp($a['name'], $b['name']); });

$perPage = 20;
$totalFiles = count($files);
$totalPages = max(1, ceil($totalFiles / $perPage));
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$paginatedFiles = array_slice($files, $offset, $perPage);

$allFolders = [];
function scanFolders($dir, &$result, $base) {
    $it = new DirectoryIterator($dir);
    foreach ($it as $item) {
        if ($item->isDot() || !$item->isDir()) continue;
        $rel = substr($item->getPathname(), strlen($base) + 1);
        $result[] = ['name' => $item->getFilename(), 'relativePath' => $rel];
        scanFolders($item->getPathname(), $result, $base);
    }
}
scanFolders($userUploadDir, $allFolders, $userUploadDir);

// 用户存储空间
$usedSpace = getUserUsedSpace($currentUser);

include 'template.php';
?>