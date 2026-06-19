<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>注册 - 个人云盘</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: linear-gradient(135deg, #e0e7ff, #f0f4ff); }
        .card { backdrop-filter: blur(10px); background: rgba(255,255,255,0.9); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="card w-full max-w-md p-8 rounded-2xl shadow-2xl">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 text-blue-600 text-3xl">
                <i class="fa fa-user-plus"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mt-4">注册新账号</h1>
            <p class="text-gray-500 mt-2">创建您的个人云盘账户</p>
        </div>

        <?php
        require_once 'config.php';
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm'] ?? '';

            if (empty($username) || empty($password)) {
                $error = '用户名和密码不能为空';
            } elseif ($password !== $confirm) {
                $error = '两次输入的密码不一致';
            } elseif (strlen($password) < 4) {
                $error = '密码长度至少4位';
            } else {
                $users = json_decode(file_get_contents(USER_DATA_FILE), true);
                if (isset($users[$username])) {
                    $error = '用户名已被注册';
                } else {
                    $users[$username] = password_hash($password, PASSWORD_DEFAULT);
                    file_put_contents(USER_DATA_FILE, json_encode($users));
                    $success = '注册成功！<a href="index.php" class="text-blue-600 underline">去登录</a>';
                }
            }
        }
        ?>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 flex items-center">
                <i class="fa fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-50 text-green-600 p-3 rounded-lg mb-4 flex items-center">
                <i class="fa fa-check-circle mr-2"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fa fa-user"></i></span>
                    <input type="text" name="username" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="请输入用户名">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">密码</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fa fa-lock"></i></span>
                    <input type="password" name="password" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="至少4位">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">确认密码</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fa fa-lock"></i></span>
                    <input type="password" name="confirm" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="再次输入密码">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition duration-200">
                <i class="fa fa-user-plus mr-2"></i> 立即注册
            </button>
        </form>
        <p class="text-center text-sm text-gray-400 mt-4">
            已有账号？ <a href="index.php" class="text-blue-600 hover:underline">去登录</a>
        </p>
    </div>
</body>
</html>