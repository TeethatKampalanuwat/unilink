<?php
require_once '../includes/functions.php';

$mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);
$errors = [];
$old = ['name' => '', 'email' => ''];

if (current_user()) redirect($next);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $old['email'] = $email;

    if ($mode === 'register') {
        $name = trim($_POST['name'] ?? '');
        $old['name'] = $name;
        $confirm = $_POST['confirm'] ?? '';
        if ($name === '') $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
        if (!is_chula_email($email)) $errors[] = 'ต้องใช้อีเมลจุฬาฯ (@chula.ac.th หรือ @student.chula.ac.th)';
        if (strlen($pass) < 6) $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
        if ($pass !== $confirm) $errors[] = 'ยืนยันรหัสผ่านไม่ตรงกัน';
        if (!$errors) {
            $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
            $st->execute([$email]);
            if ($st->fetch()) $errors[] = 'อีเมลนี้มีบัญชีอยู่แล้ว กรุณาเข้าสู่ระบบ';
        }
        if (!$errors) {
            $id = new_user_id($email);
            $st = $pdo->prepare("INSERT INTO users (user_id, user_name, email, password, role) VALUES (?, ?, ?, ?, 'user')");
            $st->execute([$id, $name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            flash('สมัครสมาชิกสำเร็จ! ลองเติมข้อมูลโปรไฟล์และ Skills ให้ครบ เพื่อให้ Recruiter รู้จักคุณมากขึ้น');
            redirect('profile.php');
        }
    } else {
        $st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();
        if (!$user || !password_verify($pass, $user['password'])) {
            $errors[] = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
        } elseif ($user['role'] === 'guest') {
            $errors[] = 'บัญชีนี้ถูกจำกัดสิทธิ์ กรุณาติดต่อผู้ดูแลระบบ';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            redirect($next);
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $mode === 'register' ? 'สมัครสมาชิก' : 'เข้าสู่ระบบ' ?> · UniLink</title>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth">
  <div class="auth-side">
    <div class="auth-logo">🎓</div>
    <h1>UniLink</h1>
    <p>Chula Activity Hub</p>
  </div>
  <div class="auth-form">
    <form method="post" action="login.php?mode=<?= $mode ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <?php if ($mode === 'register'): ?>
        <h2>สร้างบัญชีใหม่ ✨</h2>
        <p class="muted">สมัครสมาชิกเพื่อเริ่มติดตามกิจกรรมในจุฬาฯ</p>
      <?php else: ?>
        <h2>ยินดีต้อนรับกลับมา! 👋</h2>
        <p class="muted">กรุณาเข้าสู่ระบบเพื่อใช้งานระบบ UniLink</p>
      <?php endif; ?>

      <?php foreach (flash() as [$t, $m]): ?><div class="alert alert-<?= e($t) ?>"><?= e($m) ?></div><?php endforeach; ?>
      <?php if ($errors): ?>
        <div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
      <?php endif; ?>

      <?php if ($mode === 'register'): ?>
        <label>ชื่อ-นามสกุล<input name="name" value="<?= e($old['name']) ?>" placeholder="ภาษาไทยหรืออังกฤษ" required></label>
      <?php endif; ?>
      <label>อีเมลนิสิต (@chula.ac.th)<input type="email" name="email" value="<?= e($old['email']) ?>" placeholder="6xxxxxxxxx@student.chula.ac.th" required></label>
      <label>รหัสผ่าน
        <span class="pw"><input type="password" name="password" placeholder="••••••••" required><button type="button" class="pw-toggle" aria-label="แสดงรหัสผ่าน">👁</button></span>
      </label>
      <?php if ($mode === 'register'): ?>
        <label>ยืนยันรหัสผ่านอีกครั้ง
          <span class="pw"><input type="password" name="confirm" placeholder="••••••••" required><button type="button" class="pw-toggle" aria-label="แสดงรหัสผ่าน">👁</button></span>
        </label>
        <button class="btn btn-primary btn-block">ลงทะเบียนสมาชิก</button>
        <p class="center muted">มีบัญชีอยู่แล้ว? <a class="pink" href="login.php?mode=login&next=<?= urlencode($next) ?>">เข้าสู่ระบบ</a></p>
      <?php else: ?>
        <button class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
        <p class="center muted">ยังไม่มีบัญชี UniLink? <a class="pink" href="login.php?mode=register&next=<?= urlencode($next) ?>">สมัครที่นี่</a></p>
      <?php endif; ?>
      <p class="center small"><a href="home.php">← กลับหน้าหลัก</a> · <a href="admin_login.php">Admin</a></p>
    </form>
  </div>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
