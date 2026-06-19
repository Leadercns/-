<?php
require_once 'config.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
if (!$token) die('缺少分享令牌');

$shares = json_decode(file_get_contents(SHARES_FILE), true);
if (!isset($shares[$token])) die('分享链接无效或已过期');

$share = $shares[$token];
$user = $share['user'] ?? '';
if (!$user) die('分享数据异常');

$filePath = getUserUploadDir($user) . '/' . $share['file'];

// 检查过期
if ($share['expire'] && time() > $share['expire']) {
    unset($shares[$token]);
    file_put_contents(SHARES_FILE, json_encode($shares));
    die('分享已过期');
}

if (!file_exists($filePath) || !is_file($filePath)) {
    die('文件不存在');
}

$fileName = basename($filePath);
$mime = getMimeType($filePath);
$size = filesize($filePath);

// 判断当前用户是否已登录，且不是文件所有者
$isLoggedIn = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$currentUser = $isLoggedIn ? $_SESSION['username'] : '';
$isOwner = ($currentUser === $user);

// 在线预览
$preview = ['image/jpeg','image/png','image/gif','image/webp','application/pdf','video/mp4','audio/mpeg'];
$isPreview = in_array($mime, $preview);

?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title><?= htmlspecialchars($fileName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
    .btn-primary { background: #2563eb; color: white; }
    .btn-primary:hover { background: #1d4ed8; }
    .btn-success { background: #22c55e; color: white; }
    .btn-success:hover { background: #16a34a; }
    .btn-gray { background: #e5e7eb; color: #374151; }
    .btn-gray:hover { background: #d1d5db; }
    .modal-overlay { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
    .modal-card { transition: all 0.3s ease; }
</style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100 p-4">
<div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full p-6">
    <?php if ($isPreview): ?>
        <?php if (strpos($mime, 'image/') === 0): ?>
            <img src="uploads/<?= $user ?>/<?= urlencode($share['file']) ?>" class="max-w-full max-h-[70vh] mx-auto rounded">
        <?php elseif ($mime === 'application/pdf'): ?>
            <embed src="uploads/<?= $user ?>/<?= urlencode($share['file']) ?>" type="application/pdf" class="w-full h-[70vh] rounded">
        <?php elseif (strpos($mime, 'video/') === 0): ?>
            <video controls class="w-full rounded"><source src="uploads/<?= $user ?>/<?= urlencode($share['file']) ?>"></video>
        <?php elseif (strpos($mime, 'audio/') === 0): ?>
            <audio controls class="w-full"><source src="uploads/<?= $user ?>/<?= urlencode($share['file']) ?>"></audio>
        <?php else: ?>
            <p class="text-center text-gray-500">此文件类型暂不支持在线预览，请下载。</p>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center py-10">
            <i class="fa fa-file-o text-6xl text-gray-300"></i>
            <p class="text-gray-500 mt-4">此文件类型不支持在线预览，请下载。</p>
        </div>
    <?php endif; ?>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <span class="text-sm text-gray-600"><?= htmlspecialchars($fileName) ?> (<?= formatSize($size) ?>)</span>
        <div class="flex flex-wrap gap-2">
            <a href="uploads/<?= $user ?>/<?= urlencode($share['file']) ?>" download class="btn-primary px-4 py-2 rounded-lg text-sm flex items-center">
                <i class="fa fa-download mr-2"></i>下载
            </a>
            <?php if ($isLoggedIn && !$isOwner): ?>
                <button id="transferBtn" class="btn-success px-4 py-2 rounded-lg text-sm flex items-center">
                    <i class="fa fa-cloud-upload mr-2"></i>转存到我的云盘
                </button>
            <?php elseif (!$isLoggedIn): ?>
                <a href="index.php" class="btn-gray px-4 py-2 rounded-lg text-sm flex items-center">
                    <i class="fa fa-sign-in mr-2"></i>登录后转存
                </a>
            <?php elseif ($isOwner): ?>
                <span class="text-sm text-gray-400 bg-gray-100 px-3 py-2 rounded-lg">这是您自己的文件</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 自定义提示框（与主界面风格一致） -->
<div id="customAlert" class="fixed inset-0 modal-overlay hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full mx-4 p-6 modal-card">
        <div class="flex items-start">
            <div class="flex-shrink-0 text-blue-500 text-2xl mr-3"><i class="fa fa-info-circle"></i></div>
            <div class="flex-1">
                <h3 id="alertTitle" class="text-lg font-semibold">提示</h3>
                <p id="alertMessage" class="text-gray-600 mt-2">消息内容</p>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button onclick="closeAlert()" class="btn-primary px-4 py-2 rounded-lg text-sm">确定</button>
        </div>
    </div>
</div>

<script>
function showAlert(title, message) {
    document.getElementById('alertTitle').textContent = title;
    document.getElementById('alertMessage').textContent = message;
    document.getElementById('customAlert').classList.remove('hidden');
}
function closeAlert() {
    document.getElementById('customAlert').classList.add('hidden');
}

document.getElementById('transferBtn')?.addEventListener('click', function() {
    const token = '<?= htmlspecialchars($token) ?>';
    // 发起 AJAX 请求转存
    fetch('index.php?action=transfer&token=' + encodeURIComponent(token))
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('转存成功', data.message);
            } else {
                showAlert('转存失败', data.message || '未知错误');
            }
        })
        .catch(() => {
            showAlert('请求失败', '网络错误，请稍后重试');
        });
});
</script>
</body>
</html>