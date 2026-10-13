<?php
// ติดตั้ง/รีเซ็ตฐานข้อมูล UniLink จากไฟล์ database/unilink.sql
// ใช้ได้เฉพาะจากเครื่องตัวเอง (localhost) เท่านั้น
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Forbidden');
}
require __DIR__ . '/config/db_settings.php';
header('Content-Type: text/html; charset=utf-8');
try {
    $pdo = new PDO("mysql:host=$DB_HOST;port=$DB_PORT;charset=utf8mb4", $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $exists = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($DB_NAME))->fetchColumn();
    if ($exists && empty($_GET['reset'])) {
        exit("<p>ฐานข้อมูล <b>$DB_NAME</b> ติดตั้งแล้ว ($exists ตาราง)</p><p><a href='index.php'>ไปหน้าเว็บ</a> · <a href='install.php?reset=1' onclick=\"return confirm('ลบข้อมูลทั้งหมดแล้วติดตั้งใหม่?')\">รีเซ็ตเป็นข้อมูลตัวอย่าง</a></p>");
    }
    $sql = file_get_contents(__DIR__ . '/database/unilink.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', preg_split("/;\\s*\\R/", $sql))) as $stmt) {
        $pdo->exec($stmt);
    }
    $pdo->exec("USE `$DB_NAME`");
    $n = $pdo->query('SELECT (SELECT COUNT(*) FROM users), (SELECT COUNT(*) FROM activity), (SELECT COUNT(*) FROM application)')->fetch(PDO::FETCH_NUM);
    echo "<h2>✅ ติดตั้งฐานข้อมูลสำเร็จ</h2><p>users: $n[0] · activity: $n[1] · application: $n[2]</p><p><a href='index.php'>ไปหน้าเว็บ UniLink →</a></p>";
} catch (PDOException $e) {
    echo '<h2>❌ ติดตั้งไม่สำเร็จ</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
