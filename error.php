<?php
$local404 = __DIR__ . '/404.php';

if (file_exists($local404)) {
    http_response_code(404); // 保持 404 状态码
    readfile($local404);
} else {
    echo '<h1>404 Not Found</h1>';
}
exit;
?>