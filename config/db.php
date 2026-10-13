<?php
require __DIR__ . '/db_settings.php';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('<h3>เชื่อมต่อฐานข้อมูลไม่ได้</h3><p>ตรวจสอบว่าเปิด MySQL ใน XAMPP แล้ว และ ติดตั้งฐานข้อมูลแล้ว (เปิด <a href="../install.php">install.php</a>)</p><pre>'
        . htmlspecialchars($e->getMessage()) . '</pre>');
}
