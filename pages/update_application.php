<?php
// อนุมัติ / ปฏิเสธใบสมัคร (เฉพาะเจ้าของกิจกรรม หรือ admin)
require_once '../includes/functions.php';

$me = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('dashboard.php');
check_csrf();

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

$st = $pdo->prepare('SELECT ap.*, a.user_id AS owner_id, a.act_id, u.user_name
    FROM application ap JOIN activity a ON a.act_id = ap.activity_id JOIN users u ON u.user_id = ap.user_id
    WHERE ap.id = ?');
$st->execute([$id]);
$ap = $st->fetch();
if (!$ap || ($ap['owner_id'] !== $me['user_id'] && $me['role'] !== 'admin')) {
    flash('ไม่พบใบสมัคร หรือคุณไม่มีสิทธิ์', 'error');
    redirect('dashboard.php');
}

if ($action === 'approve') {
    $pdo->prepare("UPDATE application SET status = 'approved', reject_reason = NULL WHERE id = ?")->execute([$id]);
    flash('ตอบรับ ' . $ap['user_name'] . ' แล้ว ✅');
} elseif ($action === 'reject') {
    $reason = trim($_POST['reason'] ?? '') ?: 'ไม่ระบุ';
    $pdo->prepare("UPDATE application SET status = 'rejected', reject_reason = ? WHERE id = ?")->execute([$reason, $id]);
    flash('ปฏิเสธ ' . $ap['user_name'] . ' แล้ว');
}
redirect('view_applicants.php?act_id=' . (int)$ap['act_id']);
