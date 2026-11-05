<?php
// ===== ตั้งค่าการเชื่อมต่อฐานข้อมูล =====
// Windows (XAMPP): user = root, password = (ว่าง), port 3306
// macOS  (MAMP) : user = root, password = root,   port 8889  ← ระบบเลือกให้อัตโนมัติ
$DB_HOST = '127.0.0.1';
$DB_NAME = 'unilink';
$DB_USER = 'root';

if (PHP_OS_FAMILY === 'Darwin' && is_dir('/Applications/MAMP')) {
    $DB_PORT = '8889';
    $DB_PASS = 'root';
} else {
    $DB_PORT = '3306';
    $DB_PASS = '';
}
