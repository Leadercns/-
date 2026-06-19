<?php
// 从 index.php 传递：$currentPath, $relativePath, $folders, $paginatedFiles, $totalFiles, $page, $totalPages, $allFolders, $usedSpace, $successMessage, $errorMessage
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>个人云盘</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .shadow-soft { box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
        .file-icon { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #f3f4f6; border-radius: 8px; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; }
        .btn-purple { background: #8b5cf6; color: white; }
        .btn-purple:hover { background: #7c3aed; }
        .btn-gray { background: #e5e7eb; color: #374151; }
        .btn-gray:hover { background: #d1d5db; }
        .modal-overlay { background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
        .modal-card { transition: all 0.3s ease; max-width: 90%; }
        @media (max-width: 640px) {
            .table-wrap { overflow-x: auto; }
            .action-btns { flex-wrap: wrap; gap: 0.5rem; }
            .action-btns button { flex: 1 0 calc(50% - 0.25rem); }
        }
        .checkbox-custom { width: 18px; height: 18px; cursor: pointer; accent-color: #2563eb; }
        .breadcrumb a { color: #2563eb; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .modal-box { background: white; border-radius: 1.5rem; padding: 1.5rem; max-width: 420px; width: 100%; margin: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .modal-box h3 { font-size: 1.25rem; font-weight: 600; color: #1e293b; }
        .modal-box p { color: #64748b; margin: 0.75rem 0 1.5rem; line-height: 1.5; }
        .modal-box .actions { display: flex; gap: 0.75rem; justify-content: flex-end; }
        .radio-group label { display: block; margin: 0.5rem 0; cursor: pointer; }
        .radio-group input[type="radio"] { margin-right: 0.5rem; }
    </style>
</head>
<body class="bg-gray-50 font-sans">

<!-- 顶部导航 -->
<header class="bg-white shadow-sm sticky top-0 z-40">
    <div class="container mx-auto px-4 py-3 flex flex-wrap items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600 text-xl">
                <i class="fa fa-cloud"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-800">个人云盘</h1>
            <nav class="hidden md:flex ml-6 space-x-2 text-sm breadcrumb">
                <a href="index.php"><i class="fa fa-home"></i> 根目录</a>
                <?php
                if (!empty($relativePath)) {
                    $parts = explode('/', $relativePath);
                    $cum = '';
                    foreach ($parts as $p) {
                        $cum .= ($cum ? '/' : '') . $p;
                        echo ' <span class="text-gray-400">/</span> <a href="?path=' . urlencode($cum) . '">' . htmlspecialchars($p) . '</a>';
                    }
                }
                ?>
            </nav>
        </div>
        <div class="flex items-center space-x-3">
            <a href="?logout=1" class="text-gray-500 hover:text-gray-700"><i class="fa fa-sign-out"></i></a>
        </div>
    </div>
</header>

<!-- 主容器 -->
<main class="container mx-auto px-4 py-6">

    <?php if (!empty($successMessage)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center">
            <i class="fa fa-check-circle mr-2"></i> <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center">
            <i class="fa fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- 左侧 -->
        <aside class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-soft p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">存储空间</h2>
                <div class="text-sm text-gray-600">
                    已用 <?= formatSize($usedSpace) ?>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">仅统计当前用户文件</p>
            </div>
            <div class="bg-white rounded-xl shadow-soft p-5">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">文件夹</h2>
                <div class="space-y-1 max-h-72 overflow-y-auto">
                    <a href="index.php" class="block px-3 py-2 rounded-lg hover:bg-gray-50 <?= empty($relativePath) ? 'bg-blue-50 text-blue-700' : '' ?>">
                        <i class="fa fa-folder text-blue-500 mr-2"></i> 根目录
                    </a>
                    <?php foreach ($folders as $f): ?>
                        <a href="?path=<?= urlencode($f['path']) ?>" class="block px-3 py-2 rounded-lg hover:bg-gray-50 <?= ($relativePath === $f['path']) ? 'bg-blue-50 text-blue-700' : '' ?>">
                            <i class="fa fa-folder text-blue-500 mr-2"></i> <?= htmlspecialchars($f['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- 右侧 -->
        <div class="lg:col-span-3">
            <!-- 移动端面包屑 -->
            <div class="md:hidden bg-white rounded-lg shadow-soft p-3 mb-4 flex justify-between items-center">
                <div class="breadcrumb text-sm">
                    <a href="index.php"><i class="fa fa-home"></i></a>
                    <?php if (!empty($relativePath)): ?>
                        <span class="text-gray-400">/</span>
                        <span class="font-medium"><?= htmlspecialchars(basename($relativePath)) ?></span>
                    <?php endif; ?>
                </div>
                <button id="mobileSearchToggle" class="text-gray-500"><i class="fa fa-search"></i></button>
            </div>
            <div id="mobileSearch" class="md:hidden hidden mb-4">
                <form method="GET" class="flex">
                    <input type="hidden" name="path" value="<?= urlencode($relativePath) ?>">
                    <input type="text" name="search" placeholder="搜索..." class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 rounded-r-lg"><i class="fa fa-search"></i></button>
                </form>
            </div>

            <div class="flex flex-wrap items-center justify-between mb-4 gap-2">
                <div class="flex flex-wrap gap-2">
                    <button id="uploadBtn" class="btn-primary px-4 py-2 rounded-lg flex items-center"><i class="fa fa-upload mr-2"></i>上传文件</button>
                    <button id="newFolderBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center"><i class="fa fa-folder mr-2"></i>新建文件夹</button>
                </div>
                <div class="text-sm text-gray-500">
                    共 <?= $totalFiles ?> 个文件，第 <?= $page ?>/<?= $totalPages ?> 页
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-soft overflow-hidden">
                <div class="overflow-x-auto table-wrap">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="masterCheckbox" class="checkbox-custom">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">名称</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">大小</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">类型</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden md:table-cell">修改时间</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($folders as $f): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3"><input type="checkbox" class="item-checkbox checkbox-custom" data-path="<?= htmlspecialchars($f['path']) ?>" disabled></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <i class="fa fa-folder text-blue-500 text-lg mr-2"></i>
                                        <a href="?path=<?= urlencode($f['path']) ?>" class="text-gray-800 hover:text-blue-600"><?= htmlspecialchars($f['name']) ?></a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-500">-</td>
                                <td class="px-4 py-3 text-gray-500 hidden md:table-cell">文件夹</td>
                                <td class="px-4 py-3 text-gray-500 hidden md:table-cell"><?= date('Y-m-d H:i', $f['time']) ?></td>
                                <td class="px-4 py-3 text-right">
                                    <button class="text-gray-400 hover:text-blue-600 action-btn" data-action="rename-folder" data-name="<?= htmlspecialchars($f['name']) ?>"><i class="fa fa-pencil"></i></button>
                                    <button class="text-gray-400 hover:text-red-600 action-btn" data-action="delete-folder" data-name="<?= htmlspecialchars($f['name']) ?>"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php foreach ($paginatedFiles as $file): 
                                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                                $icon = 'file-o';
                                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) $icon = 'file-image-o';
                                elseif ($ext === 'pdf') $icon = 'file-pdf-o';
                                elseif (in_array($ext, ['doc','docx'])) $icon = 'file-word-o';
                                elseif (in_array($ext, ['xls','xlsx'])) $icon = 'file-excel-o';
                                elseif (in_array($ext, ['zip','rar','7z'])) $icon = 'file-archive-o';
                                elseif (in_array($ext, ['mp4','avi','mov'])) $icon = 'file-video-o';
                                elseif (in_array($ext, ['mp3','wav'])) $icon = 'file-audio-o';
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3"><input type="checkbox" class="item-checkbox checkbox-custom" data-path="<?= htmlspecialchars($file['path']) ?>"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <span class="file-icon mr-2"><i class="fa fa-<?= $icon ?> text-gray-500"></i></span>
                                        <span class="text-gray-800"><?= htmlspecialchars($file['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-500"><?= formatSize($file['size']) ?></td>
                                <td class="px-4 py-3 text-gray-500 hidden md:table-cell"><?= htmlspecialchars($file['mime'] ?? '') ?></td>
                                <td class="px-4 py-3 text-gray-500 hidden md:table-cell"><?= date('Y-m-d H:i', $file['time']) ?></td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <!-- 分享/撤销分享按钮 -->
                                    <?php if (!empty($file['share_token'])): ?>
                                        <button class="text-gray-400 hover:text-red-600 action-btn" data-action="unshare" data-token="<?= htmlspecialchars($file['share_token']) ?>" title="撤销分享"><i class="fa fa-times-circle"></i></button>
                                    <?php else: ?>
                                        <button class="text-gray-400 hover:text-blue-600 action-btn" data-action="share" data-path="<?= htmlspecialchars($file['path']) ?>" data-name="<?= htmlspecialchars($file['name']) ?>" title="生成分享链接"><i class="fa fa-share-alt"></i></button>
                                    <?php endif; ?>
                                    <button class="text-gray-400 hover:text-blue-600 action-btn" data-action="copy-link" data-path="<?= htmlspecialchars($file['path']) ?>" data-name="<?= htmlspecialchars($file['name']) ?>" title="复制链接"><i class="fa fa-link"></i></button>
                                    <button class="text-gray-400 hover:text-blue-600 action-btn" data-action="move-file" data-path="<?= htmlspecialchars($file['path']) ?>" data-name="<?= htmlspecialchars($file['name']) ?>" title="移动"><i class="fa fa-arrows"></i></button>
                                    <button class="text-gray-400 hover:text-blue-600 action-btn" data-action="download-file" data-path="<?= htmlspecialchars($file['path']) ?>" title="下载"><i class="fa fa-download"></i></button>
                                    <button class="text-gray-400 hover:text-blue-600 action-btn" data-action="rename-file" data-path="<?= htmlspecialchars($file['path']) ?>" data-name="<?= htmlspecialchars($file['name']) ?>" title="重命名"><i class="fa fa-pencil"></i></button>
                                    <button class="text-gray-400 hover:text-red-600 action-btn" data-action="delete-file" data-path="<?= htmlspecialchars($file['path']) ?>" data-name="<?= htmlspecialchars($file['name']) ?>" title="删除"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($folders) && empty($paginatedFiles)): ?>
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400"><i class="fa fa-folder-open-o text-4xl block mb-2"></i>此文件夹为空</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-gray-200 flex flex-wrap items-center justify-between gap-2 bg-gray-50">
                    <div class="flex flex-wrap items-center gap-2 action-btns">
                        <button id="copySelectedBtn" class="btn-primary px-3 py-1.5 rounded text-sm disabled:opacity-50" disabled><i class="fa fa-link mr-1"></i>复制链接</button>
                        <button id="deleteSelectedBtn" class="btn-danger px-3 py-1.5 rounded text-sm disabled:opacity-50" disabled><i class="fa fa-trash mr-1"></i>删除</button>
                        <button id="moveSelectedBtn" class="btn-success px-3 py-1.5 rounded text-sm disabled:opacity-50" disabled><i class="fa fa-arrows mr-1"></i>移动</button>
                        <button id="downloadSelectedBtn" class="btn-purple px-3 py-1.5 rounded text-sm disabled:opacity-50" disabled><i class="fa fa-download mr-1"></i>下载</button>
                    </div>
                    <div class="flex items-center space-x-2 text-sm">
                        <a href="?path=<?= urlencode($relativePath) ?>&page=<?= max(1, $page-1) ?>" class="px-3 py-1 border rounded <?= ($page<=1)?'text-gray-300 pointer-events-none':'hover:bg-gray-100' ?>"><i class="fa fa-chevron-left"></i></a>
                        <span class="text-gray-600"><?= $page ?> / <?= $totalPages ?></span>
                        <a href="?path=<?= urlencode($relativePath) ?>&page=<?= min($totalPages, $page+1) ?>" class="px-3 py-1 border rounded <?= ($page>=$totalPages)?'text-gray-300 pointer-events-none':'hover:bg-gray-100' ?>"><i class="fa fa-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- 上传模态框 -->
<div id="uploadModal" class="fixed inset-0 modal-overlay hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 p-6 modal-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">上传文件到 <?= htmlspecialchars(basename($relativePath) ?: '根目录') ?></h3>
            <button onclick="closeModal('uploadModal')" class="text-gray-400 hover:text-gray-600"><i class="fa fa-times"></i></button>
        </div>
        <form id="uploadForm" method="POST" enctype="multipart/form-data">
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition">
                <input type="file" name="files[]" id="fileInput" multiple class="hidden">
                <label for="fileInput" class="cursor-pointer">
                    <i class="fa fa-cloud-upload text-4xl text-gray-400 mb-2"></i>
                    <p class="text-gray-600">点击或拖拽文件上传</p>
                </label>
            </div>
            <div id="progressArea" class="hidden mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2.5"><div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width:0%"></div></div>
                <span id="progressText" class="text-sm text-gray-500">0%</span>
            </div>
            <div class="mt-4 flex justify-end space-x-3">
                <button type="button" onclick="closeModal('uploadModal')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">取消</button>
                <button type="submit" class="btn-primary px-4 py-2 rounded-lg">上传</button>
            </div>
        </form>
    </div>
</div>

<!-- 通用操作模态框 -->
<div id="actionModal" class="fixed inset-0 modal-overlay hidden flex items-center justify-center z-50">
    <div id="actionModalContent" class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6 modal-card"></div>
</div>

<!-- 自定义提示框和确认框 -->
<div id="customAlert" class="fixed inset-0 modal-overlay hidden flex items-center justify-center z-[60]">
    <div class="modal-box">
        <div class="flex items-start">
            <div class="flex-shrink-0 text-blue-500 text-2xl mr-3"><i class="fa fa-info-circle"></i></div>
            <div class="flex-1">
                <h3 id="alertTitle">提示</h3>
                <p id="alertMessage">消息内容</p>
            </div>
        </div>
        <div class="actions">
            <button onclick="closeAlert()" class="btn-primary px-4 py-2 rounded-lg text-sm">确定</button>
        </div>
    </div>
</div>
<div id="customConfirm" class="fixed inset-0 modal-overlay hidden flex items-center justify-center z-[60]">
    <div class="modal-box">
        <div class="flex items-start">
            <div class="flex-shrink-0 text-yellow-500 text-2xl mr-3"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="flex-1">
                <h3 id="confirmTitle">确认操作</h3>
                <p id="confirmMessage">您确定要执行此操作吗？</p>
            </div>
        </div>
        <div class="actions">
            <button onclick="closeConfirm(false)" class="btn-gray px-4 py-2 rounded-lg text-sm">取消</button>
            <button id="confirmOkBtn" class="btn-danger px-4 py-2 rounded-lg text-sm">确定</button>
        </div>
    </div>
</div>

<script>
// 从 PHP 获取用户名和上传基础 URL
var CURRENT_USER = '<?= addslashes(getCurrentUser()) ?>';
var UPLOAD_BASE_URL = '<?= UPLOAD_BASE_URL ?>';

// ---------- 自定义 Alert ----------
function showAlert(title, message) {
    document.getElementById('alertTitle').textContent = title;
    document.getElementById('alertMessage').textContent = message;
    document.getElementById('customAlert').classList.remove('hidden');
}
function closeAlert() {
    document.getElementById('customAlert').classList.add('hidden');
}

// ---------- 自定义 Confirm ----------
let confirmCallback = null;
function showConfirm(title, message, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('customConfirm').classList.remove('hidden');
    confirmCallback = callback;
    const okBtn = document.getElementById('confirmOkBtn');
    const newOk = okBtn.cloneNode(true);
    okBtn.parentNode.replaceChild(newOk, okBtn);
    newOk.addEventListener('click', function() {
        closeConfirm(true);
    });
}
function closeConfirm(result) {
    document.getElementById('customConfirm').classList.add('hidden');
    if (typeof confirmCallback === 'function') {
        confirmCallback(result);
        confirmCallback = null;
    }
}
document.getElementById('customAlert').addEventListener('click', function(e) {
    if (e.target === this) closeAlert();
});
document.getElementById('customConfirm').addEventListener('click', function(e) {
    if (e.target === this) closeConfirm(false);
});

// ---------- 工具函数 ----------
function openModal(html) {
    document.getElementById('actionModalContent').innerHTML = html;
    document.getElementById('actionModal').classList.remove('hidden');
}
function closeModal(id = 'actionModal') {
    document.getElementById(id).classList.add('hidden');
}

// ---------- 上传 ----------
document.getElementById('uploadBtn').addEventListener('click', () => {
    document.getElementById('uploadModal').classList.remove('hidden');
});
document.getElementById('fileInput').addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('progressArea').classList.remove('hidden');
        document.getElementById('progressText').textContent = `已选 ${this.files.length} 个文件`;
    } else {
        document.getElementById('progressArea').classList.add('hidden');
    }
});
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const xhr = new XMLHttpRequest();
    const bar = document.getElementById('progressBar');
    const text = document.getElementById('progressText');
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            bar.style.width = pct + '%';
            text.textContent = pct + '%';
        }
    });
    xhr.onload = function() {
        if (xhr.status === 200) {
            location.reload();
        } else {
            showAlert('上传失败', '文件上传失败，请检查权限或网络');
        }
    };
    xhr.onerror = function() {
        showAlert('上传错误', '请求失败，请稍后重试');
    };
    xhr.open('POST', window.location.href, true);
    xhr.send(formData);
});

// ---------- 新建文件夹 ----------
document.getElementById('newFolderBtn').addEventListener('click', function() {
    openModal(`
        <h3 class="text-lg font-semibold mb-4">新建文件夹</h3>
        <form method="POST">
            <input type="text" name="folder_name" placeholder="文件夹名称" class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-4" required>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">取消</button>
                <button type="submit" name="create_folder" class="btn-primary px-4 py-2 rounded-lg">创建</button>
            </div>
        </form>
    `);
});

// ---------- 操作按钮（委托） ----------
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.action-btn');
    if (!btn) return;
    e.preventDefault();
    const action = btn.dataset.action;
    const name = btn.dataset.name || '';
    const path = btn.dataset.path || '';
    const token = btn.dataset.token || '';

    switch (action) {
        case 'rename-folder':
            openModal(`
                <h3 class="text-lg font-semibold mb-4">重命名文件夹</h3>
                <form method="POST">
                    <input type="hidden" name="old_name" value="${name}">
                    <input type="text" name="new_name" value="${name}" class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-4" required>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">取消</button>
                        <button type="submit" name="rename_folder" class="btn-primary px-4 py-2 rounded-lg">确定</button>
                    </div>
                </form>
            `);
            break;
        case 'delete-folder':
            showConfirm('删除文件夹', `确定要删除文件夹“${name}”及其所有内容吗？此操作不可撤销。`, function(confirmed) {
                if (confirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="folder_name" value="${name}"><input type="hidden" name="delete_folder" value="1">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
            break;
        case 'rename-file':
            openModal(`
                <h3 class="text-lg font-semibold mb-4">重命名文件</h3>
                <form method="POST">
                    <input type="hidden" name="old_name" value="${name}">
                    <input type="text" name="new_name" value="${name}" class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-4" required>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">取消</button>
                        <button type="submit" name="rename_file" class="btn-primary px-4 py-2 rounded-lg">确定</button>
                    </div>
                </form>
            `);
            break;
        case 'delete-file':
            showConfirm('删除文件', `确定要删除文件“${name}”吗？此操作不可撤销。`, function(confirmed) {
                if (confirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="file_name" value="${name}"><input type="hidden" name="delete_file" value="1">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
            break;
        case 'move-file':
            const folders = <?= json_encode($allFolders) ?>;
            let opts = folders.map(f => `<option value="${f.relativePath}">${f.relativePath || '根目录'}</option>`).join('');
            openModal(`
                <h3 class="text-lg font-semibold mb-4">移动文件</h3>
                <form method="POST">
                    <input type="hidden" name="file_name" value="${name}">
                    <select name="target_folder" class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-4">
                        ${opts}
                    </select>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">取消</button>
                        <button type="submit" name="move_file" class="btn-primary px-4 py-2 rounded-lg">移动</button>
                    </div>
                </form>
            `);
            break;
        case 'download-file':
            window.location.href = '?download_file=1&file=' + encodeURIComponent(path);
            break;
        case 'copy-link':
            const fileUrl = UPLOAD_BASE_URL + CURRENT_USER + '/' + path;
            const isImage = /\.(jpe?g|png|gif|webp|bmp)$/i.test(path);
            let copyText = fileUrl;
            if (isImage) {
                showConfirm('复制选项', '是否复制为 Markdown 格式？\n点击“确定”复制Markdown，点击“取消”复制纯链接', function(confirmed) {
                    if (confirmed) {
                        copyText = `![${name}](${fileUrl})`;
                    }
                    doCopy(copyText);
                });
            } else {
                doCopy(copyText);
            }
            function doCopy(text) {
                navigator.clipboard.writeText(text).then(() => {
                    showAlert('复制成功', '链接已复制到剪贴板！');
                }).catch(() => {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    ta.remove();
                    showAlert('复制成功', '链接已复制到剪贴板！');
                });
            }
            break;

        // ========== 生成分享（自定义有效期） ==========
        case 'share':
            const sharePath = path;
            const shareName = name;
            openModal(`
                <h3 class="text-lg font-semibold mb-4">生成分享链接</h3>
                <p class="text-sm text-gray-600 mb-3">文件：<strong>${shareName}</strong></p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">有效期</label>
                    <div class="radio-group">
                        <label><input type="radio" name="expire" value="86400"> 1天</label>
                        <label><input type="radio" name="expire" value="259200"> 3天</label>
                        <label><input type="radio" name="expire" value="604800" checked> 7天（默认）</label>
                        <label><input type="radio" name="expire" value="2592000"> 30天</label>
                        <label><input type="radio" name="expire" value=""> 永久</label>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">取消</button>
                    <button id="confirmShareBtn" class="btn-primary px-4 py-2 rounded-lg">生成分享</button>
                </div>
            `);
            document.getElementById('confirmShareBtn').addEventListener('click', function() {
                const selected = document.querySelector('input[name="expire"]:checked');
                let expire = selected ? selected.value : '';
                let url = '?action=share&file=' + encodeURIComponent(sharePath);
                if (expire !== '') {
                    url += '&expire=' + expire;
                }
                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            showAlert('分享失败', data.error);
                        } else {
                            closeModal();
                            openModal(`
                                <h3 class="text-lg font-semibold mb-4">分享链接已生成</h3>
                                <div class="flex items-center border border-gray-300 rounded-lg p-2 mb-4">
                                    <input type="text" id="shareUrl" value="${data.url}" class="flex-1 outline-none text-sm" readonly>
                                    <button onclick="copyShareUrl()" class="btn-primary px-3 py-1 rounded text-sm ml-2">复制</button>
                                </div>
                                <div class="flex justify-end">
                                    <button onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">关闭</button>
                                </div>
                            `);
                            window.copyShareUrl = function() {
                                const url = document.getElementById('shareUrl').value;
                                navigator.clipboard.writeText(url).then(() => {
                                    showAlert('复制成功', '分享链接已复制到剪贴板');
                                });
                            };
                        }
                    })
                    .catch(() => showAlert('请求失败', '生成分享链接时发生错误，请重试'));
            });
            break;

        // ========== 撤销分享 ==========
        case 'unshare':
            if (!token) {
                showAlert('错误', '缺少分享标识');
                return;
            }
            showConfirm('撤销分享', '确定要撤销该文件的分享链接吗？撤销后链接将立即失效。', function(confirmed) {
                if (confirmed) {
                    fetch('?action=unshare&token=' + encodeURIComponent(token))
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showAlert('撤销成功', '分享链接已撤销');
                                // 刷新页面以更新按钮状态
                                location.reload();
                            } else {
                                showAlert('撤销失败', data.error || '未知错误');
                            }
                        })
                        .catch(() => showAlert('请求失败', '网络错误，请重试'));
                }
            });
            break;
    }
});

// ---------- 全选 / 批量 ----------
const masterCheckbox = document.getElementById('masterCheckbox');
const copyBtn = document.getElementById('copySelectedBtn');
const deleteBtn = document.getElementById('deleteSelectedBtn');
const moveBtn = document.getElementById('moveSelectedBtn');
const downloadBtn = document.getElementById('downloadSelectedBtn');

function updateBatchButtons() {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    const count = checked.length;
    const disabled = count === 0;
    [copyBtn, deleteBtn, moveBtn, downloadBtn].forEach(btn => btn.disabled = disabled);
    if (masterCheckbox) {
        const all = document.querySelectorAll('.item-checkbox:not([disabled])');
        masterCheckbox.checked = all.length > 0 && all.length === checked.length;
    }
}
masterCheckbox.addEventListener('change', function() {
    document.querySelectorAll('.item-checkbox:not([disabled])').forEach(cb => cb.checked = this.checked);
    updateBatchButtons();
});
document.querySelectorAll('.item-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBatchButtons);
});
updateBatchButtons();

// 批量复制链接
copyBtn.addEventListener('click', function() {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    if (checked.length === 0) {
        showAlert('提示', '请先选择要复制链接的文件');
        return;
    }
    let links = [];
    checked.forEach(cb => {
        const path = cb.dataset.path;
        const url = UPLOAD_BASE_URL + CURRENT_USER + '/' + path;
        const name = cb.closest('tr').querySelector('td:nth-child(2) .text-gray-800')?.textContent || 'file';
        const isImage = /\.(jpe?g|png|gif|webp|bmp)$/i.test(path);
        links.push(isImage ? `![${name.trim()}](${url})` : url);
    });
    const text = links.join('\n');
    navigator.clipboard.writeText(text).then(() => {
        showAlert('复制成功', `已复制 ${checked.length} 个链接到剪贴板`);
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
        showAlert('复制成功', `已复制 ${checked.length} 个链接到剪贴板`);
    });
});

// 批量删除
deleteBtn.addEventListener('click', function() {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    if (checked.length === 0) {
        showAlert('提示', '请先选择要删除的文件');
        return;
    }
    showConfirm('批量删除', `确定删除选中的 ${checked.length} 个文件吗？此操作不可撤销。`, function(confirmed) {
        if (confirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="delete_selected_files" value="1">';
            checked.forEach(cb => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'selected_files[]';
                inp.value = cb.dataset.path;
                form.appendChild(inp);
            });
            document.body.appendChild(form);
            form.submit();
        }
    });
});

// 批量移动
moveBtn.addEventListener('click', function() {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    if (checked.length === 0) {
        showAlert('提示', '请先选择要移动的文件');
        return;
    }
    const folders = <?= json_encode($allFolders) ?>;
    let opts = folders.map(f => `<option value="${f.relativePath}">${f.relativePath || '根目录'}</option>`).join('');
    openModal(`
        <h3 class="text-lg font-semibold mb-4">移动选中的文件</h3>
        <form method="POST" id="batchMoveForm">
            <input type="hidden" name="move_selected_files" value="1">
            ${Array.from(checked).map(cb => `<input type="hidden" name="selected_files[]" value="${cb.dataset.path}">`).join('')}
            <select name="target_folder" class="w-full border border-gray-300 rounded-lg px-4 py-2 mb-4">
                ${opts}
            </select>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-50">取消</button>
                <button type="submit" class="btn-primary px-4 py-2 rounded-lg">移动</button>
            </div>
        </form>
    `);
});

// 批量下载
downloadBtn.addEventListener('click', function() {
    const checked = document.querySelectorAll('.item-checkbox:checked');
    if (checked.length === 0) {
        showAlert('提示', '请先选择要下载的文件');
        return;
    }
    const paths = Array.from(checked).map(cb => cb.dataset.path);
    window.location.href = '?download_selected_files=1&files=' + encodeURIComponent(paths.join(','));
});

// 移动端搜索切换
document.getElementById('mobileSearchToggle')?.addEventListener('click', function() {
    document.getElementById('mobileSearch').classList.toggle('hidden');
});

// 点击模态框外部关闭
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.add('hidden');
    });
});
</script>
</body>
</html>