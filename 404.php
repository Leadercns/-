<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - 页面未找到</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: linear-gradient(135deg, #e0e7ff, #f0f4ff); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: sans-serif; }
        .card { background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border-radius: 2rem; box-shadow: 0 20px 60px rgba(0,0,0,0.1); padding: 3rem 2rem; max-width: 500px; width: 100%; text-align: center; }
        .icon { font-size: 6rem; color: #2563eb; margin-bottom: 1rem; }
        h1 { font-size: 4rem; font-weight: 800; color: #1e293b; margin: 0; }
        p { color: #64748b; font-size: 1.1rem; margin: 1rem 0 2rem; }
        .btn { background: #2563eb; color: white; padding: 0.75rem 2rem; border-radius: 9999px; text-decoration: none; display: inline-block; transition: background 0.2s; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fa fa-file-text-o"></i></div>
        <h1>404</h1>
        <p>哎呀！您访问的页面不存在或已被移除。</p>
        <a href="/" class="btn"><i class="fa fa-home mr-2"></i>返回云盘首页</a>
        <div class="mt-6 text-sm text-gray-400">如果您确信这是错误，请联系管理员。</div>
    </div>
</body>
</html>