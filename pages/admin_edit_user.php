<?php
require_once '../includes/functions.php';
require_once '../includes/layout.php';

$admin = require_admin();
$id = $_GET['id'] ?? '';
$st = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
$st->execute([$id]);
$u = $st->fetch();
if (!$u) { flash('ไม่พบผู้ใช้', 'error'); redirect('admin_manage_user.php'); }
$isSelf = $u['user_id'] === $admin['user_id'];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    if (($_POST['action'] ?? '') === 'delete') {
        if ($isSelf) {
            flash('ไม่สามารถลบบัญชีของตัวเองได้', 'error');
        } else {
            $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$id]); // ลบโพสต์+ใบสมัครตาม cascade
            delete_upload($u['profile_pic']);
            flash('ลบผู้ใช้ ' . $u['user_name'] . ' แล้ว');
        }
        redirect('admin_manage_user.php');
    }

    $f = [];
    foreach (['user_name', 'email', 'role', 'faculty', 'major', 'phone', 'line_id', 'ig', 'bio', 'skills'] as $k) {
        $f[$k] = trim($_POST[$k] ?? '');
    }
    $f['email'] = strtolower($f['email']);
    if ($f['user_name'] === '') $errors[] = 'กรุณากรอกชื่อ';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'อีเมลไม่ถูกต้อง';
    if (!in_array($f['role'], ['admin', 'user', 'guest'], true)) $errors[] = 'Role ไม่ถูกต้อง';
    if ($isSelf && $f['role'] !== 'admin') $errors[] = 'ไม่สามารถลดสิทธิ์ admin ของตัวเองได้';
    $chk = $pdo->prepare('SELECT 1 FROM users WHERE email = ? AND user_id <> ?');
    $chk->execute([$f['email'], $id]);
    if ($chk->fetch()) $errors[] = 'อีเมลนี้ถูกใช้โดยผู้ใช้อื่นแล้ว';

    $passSql = '';
    $passParam = [];
    $newPass = $_POST['new_password'] ?? '';
    if ($newPass !== '') {
        if (strlen($newPass) < 6) $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
        else { $passSql = ', password = ?'; $passParam[] = password_hash($newPass, PASSWORD_DEFAULT); }
    }

    if (!$errors) {
        $sql = "UPDATE users SET user_name=?, email=?, role=?, faculty=?, major=?, phone=?, line_id=?, ig=?, bio=?, skills=? $passSql WHERE user_id=?";
        $pdo->prepare($sql)->execute(array_merge(
            [$f['user_name'], $f['email'], $f['role']],
            array_map(fn($v) => $v === '' ? null : $v, [$f['faculty'], $f['major'], $f['phone'], $f['line_id'], $f['ig'], $f['bio'], $f['skills']]),
            $passParam, [$id]
        ));
        flash('บันทึกข้อมูลผู้ใช้เรียบร้อย');
        redirect('admin_manage_user.php');
    }
    $u = array_merge($u, $f);
}

page_start('แก้ไขผู้ใช้', 'admin_users', ['admin' => true]);
?>
<form class="panel narrow dark-form" method="post" id="userForm">
  <?= csrf_field() ?>
  <h2>แก้ไขโปรไฟล์</h2>
  <p class="pink small">👤 ID: <?= e($u['user_id']) ?> · <?= e($u['user_name']) ?></p>
  <?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>
  <div class="grid-2">
    <label>Username<input name="user_name" value="<?= e($u['user_name']) ?>" required></label>
    <label>Email Address<input type="email" name="email" value="<?= e($u['email']) ?>" required></label>
    <label>Role
      <select name="role">
        <?php foreach (['user' => 'User', 'admin' => 'Admin', 'guest' => 'Guest (ระงับการใช้งาน)'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Faculty<input name="faculty" value="<?= e($u['faculty']) ?>"></label>
    <label>Major<input name="major" value="<?= e($u['major']) ?>"></label>
    <label>Phone Number<input name="phone" value="<?= e($u['phone']) ?>"></label>
    <label>Line ID<input name="line_id" value="<?= e($u['line_id']) ?>"></label>
    <label>Instagram<input name="ig" value="<?= e($u['ig']) ?>"></label>
  </div>
  <label>Skills<input name="skills" value="<?= e($u['skills']) ?>"></label>
  <label>Short Bio<textarea name="bio" rows="3"><?= e($u['bio']) ?></textarea></label>
  <label>ตั้งรหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)<input type="password" name="new_password" autocomplete="new-password"></label>

  <div class="form-footer">
    <?php if (!$isSelf): ?>
      <button class="btn btn-danger-outline" name="action" value="delete" formnovalidate data-confirm-btn="ลบผู้ใช้นี้ รวมถึงโพสต์และใบสมัครทั้งหมดของเขา?">🗑 Delete User</button>
    <?php endif; ?>
    <span class="grow"></span>
    <a class="btn btn-dark" href="admin_manage_user.php">Cancel</a>
    <button class="btn btn-primary" name="action" value="save">✓ Save Changes</button>
  </div>
</form>
<?php page_end(); ?>
