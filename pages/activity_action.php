<?php
// เปิด/ปิดรับสมัคร หรือ ลบกิจกรรม (เจ้าของโพสต์ หรือ admin)
require_once '../includes/functions.php';

$me = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('dashboard.php');
check_csrf();

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$back = safe_next($_POST['back'] ?? null, 'dashboard.php');

$st = $pdo->prepare('SELECT * FROM activity WHERE act_id = ?');
$st->execute([$id]);
$a = $st->fetch();
if (!$a || ($a['user_id'] !== $me['user_id'] && $me['role'] !== 'admin')) {
    flash('ไม่พบกิจกรรม หรือคุณไม่มีสิทธิ์', 'error');
    redirect($back);
}

if ($action === 'toggle') {
    $new = $a['status'] === 'open' ? 'closed' : 'open';
    $pdo->prepare('UPDATE activity SET status = ? WHERE act_id = ?')->execute([$new, $id]);
    flash($new === 'open' ? 'เปิดรับสมัครอีกครั้งแล้ว' : 'ปิดรับสมัครเรียบร้อย');
} elseif ($action === 'delete') {
    $pdo->prepare('DELETE FROM activity WHERE act_id = ?')->execute([$id]); // application ถูกลบตาม (ON DELETE CASCADE)
    delete_upload($a['image_path']);
    flash('ลบกิจกรรม “' . $a['title'] . '” แล้ว');
}
redirect($back);
