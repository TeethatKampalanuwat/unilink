<?php
// Admin แก้ไขกิจกรรมใดก็ได้ — ใช้ฟอร์มเดียวกับ create.php
$ADMIN_CONTEXT = true;
if (empty($_GET['id'])) {
    header('Location: admin_manage_activity.php');
    exit;
}
require __DIR__ . '/create.php';
