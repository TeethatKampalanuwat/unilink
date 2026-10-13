<?php
require_once '../includes/functions.php';

if (is_admin()) redirect('admin_dashboard.php');

$error = '';
$old = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $old = trim($_POST['admin_id'] ?? '');
    $st = $pdo->prepare("SELECT * FROM users WHERE (user_id = ? OR email = ?) AND role = 'admin'");
    $st->execute([$old, $old]);
    $admin = $st->fetch();
    if ($admin && password_verify($_POST['password'] ?? '', $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $admin['user_id'];
        redirect('admin_dashboard.php');
    }
    $error = 'Admin ID หรือรหัสผ่านไม่ถูกต้อง';
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login · UniLink</title>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-login-page">
  <form class="admin-login" method="post">
    <?= csrf_field() ?>
    <span class="pill-label">ADMIN CONTROL PANEL</span>
    <h1>UniLink Admin</h1>
    <p class="muted">กรุณาลงชื่อเข้าใช้เพื่อจัดการระบบ</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <label>Admin ID (รหัสพนักงาน)<input name="admin_id" value="<?= e($old) ?>" placeholder="admin_01" required></label>
    <label>Password (รหัสผ่าน)
      <span class="pw"><input type="password" name="password" required><button type="button" class="pw-toggle" aria-label="แสดงรหัสผ่าน">👁</button></span>
    </label>
    <button class="btn btn-primary btn-block">เข้าสู่ระบบหลังบ้าน</button>
    <p class="center small"><a href="login.php">← กลับไปหน้าล็อกอินนิสิต</a></p>
  </form>
<script src="../assets/js/app.js"></script>
</body>
</html>
