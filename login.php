<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>登录 - 个人云盘</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: linear-gradient(135deg, #e0e7ff, #f0f4ff); }
        .login-card { backdrop-filter: blur(10px); background: rgba(255,255,255,0.9); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="login-card w-full max-w-md p-8 rounded-2xl shadow-2xl">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 text-blue-600 text-3xl">
                <i class="fa fa-cloud"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mt-4">个人云盘</h1>
            <p class="text-gray-500 mt-2">请输入用户名和密码登录</p>
            <p class="text-gray-500 mt-2">注册功能已关闭！</p>
        </div>

        <?php if (isset($loginError)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 flex items-center">
                <i class="fa fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($loginError) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fa fa-user"></i></span>
                    <input type="text" name="username" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="用户名">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">密码</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fa fa-lock"></i></span>
                    <input type="password" name="password" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="******">
                </div>
            </div>
            <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg transition duration-200 flex items-center justify-center">
                <i class="fa fa-sign-in mr-2"></i> 登 录
            </button>
        </form>
        <p class="text-center text-sm text-gray-400 mt-4">
            还没有账号？ <a href="#" class="text-blue-600 hover:underline">立即注册</a>
        </p>
    </div>
</body>
</html>