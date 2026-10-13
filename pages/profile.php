<?php
require_once '../includes/functions.php';
require_once '../includes/layout.php';

$me = require_login();
$viewId = $_GET['id'] ?? $me['user_id'];
$isSelf = $viewId === $me['user_id'];

// ---------- ดูโปรไฟล์คนอื่น (เช่น Recruiter ดูผู้สมัคร) ----------
if (!$isSelf) {
    $st = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
    $st->execute([$viewId]);
    $p = $st->fetch();
    if (!$p) { flash('ไม่พบผู้ใช้', 'error'); redirect('home.php'); }

    $h = $pdo->prepare("SELECT a.title, a.category, ap.role FROM application ap JOIN activity a ON a.act_id = ap.activity_id
                        WHERE ap.user_id = ? AND ap.status = 'approved' ORDER BY ap.created_at DESC");
    $h->execute([$viewId]);
    $portfolio = $h->fetchAll();

    page_start($p['user_name'], '');
    ?>
    <div class="panel narrow">
      <a class="btn btn-light btn-sm" href="javascript:history.back()">← ย้อนกลับ</a>
      <div class="profile-head">
        <?= avatar_html($p, 'xl') ?>
        <div>
          <h2><?= e($p['user_name']) ?></h2>
          <p class="muted"><?= e($p['faculty'] ?: '-') ?> · <?= e($p['major'] ?: '-') ?></p>
        </div>
      </div>
      <?php if ($p['bio']): ?><p><?= nl2br(e($p['bio'])) ?></p><?php endif; ?>
      <?php if ($p['skills']): ?>
        <div class="tags"><?php foreach (array_filter(array_map('trim', explode(',', $p['skills']))) as $s): ?><span class="tag"><?= e($s) ?></span><?php endforeach; ?></div>
      <?php endif; ?>
      <h3>ช่องทางติดต่อ</h3>
      <ul class="contact-list">
        <li>📧 <?= e($p['email']) ?></li>
        <?php if ($p['phone']): ?><li>📞 <?= e($p['phone']) ?></li><?php endif; ?>
        <?php if ($p['line_id']): ?><li>💬 LINE: <?= e($p['line_id']) ?></li><?php endif; ?>
        <?php if ($p['ig']): ?><li>📷 IG: <?= e($p['ig']) ?></li><?php endif; ?>
      </ul>
      <h3>Portfolio (กิจกรรมที่ผ่านการคัดเลือก)</h3>
      <?php if (!$portfolio): ?><p class="muted">ยังไม่มี</p><?php endif; ?>
      <ul class="contact-list">
        <?php foreach ($portfolio as $it): ?><li>✅ <?= e($it['title']) ?> <span class="muted small">(<?= e($it['category']) ?> · <?= e($it['role']) ?>)</span></li><?php endforeach; ?>
      </ul>
    </div>
    <?php
    page_end();
    exit;
}

// ---------- แก้ไขโปรไฟล์ตัวเอง ----------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $f = [];
    foreach (['user_name', 'faculty', 'major', 'phone', 'line_id', 'ig', 'bio', 'skills', 'dob'] as $k) {
        $f[$k] = trim($_POST[$k] ?? '');
    }
    if ($f['user_name'] === '') $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    if ($f['phone'] !== '' && !preg_match('/^[0-9+\- ]{6,20}$/', $f['phone'])) $errors[] = 'รูปแบบเบอร์โทรไม่ถูกต้อง';
    if ($f['dob'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['dob'])) $errors[] = 'รูปแบบวันเกิดไม่ถูกต้อง';
    // จัดรูป skills: ตัดช่องว่าง, ลบซ้ำ
    $f['skills'] = implode(', ', array_unique(array_filter(array_map('trim', explode(',', $f['skills'])))));

    $pic = $me['profile_pic'];
    try {
        $new = upload_image('profile_pic', 'profile');
        if ($new) { delete_upload($pic); $pic = $new; }
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }

    // เปลี่ยนรหัสผ่าน (ไม่บังคับ)
    $newPass = $_POST['new_password'] ?? '';
    $passSql = '';
    $passParam = [];
    if ($newPass !== '') {
        if (!password_verify($_POST['old_password'] ?? '', $me['password'])) $errors[] = 'รหัสผ่านเดิมไม่ถูกต้อง';
        elseif (strlen($newPass) < 6) $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
        else { $passSql = ', password = ?'; $passParam = [password_hash($newPass, PASSWORD_DEFAULT)]; }
    }

    if (!$errors) {
        $st = $pdo->prepare("UPDATE users SET user_name=?, faculty=?, major=?, phone=?, line_id=?, ig=?, bio=?, skills=?, dob=?, profile_pic=? $passSql WHERE user_id=?");
        $st->execute(array_merge([
            $f['user_name'], $f['faculty'] ?: null, $f['major'] ?: null, $f['phone'] ?: null,
            $f['line_id'] ?: null, $f['ig'] ?: null, $f['bio'] ?: null, $f['skills'] ?: null,
            $f['dob'] ?: null, $pic,
        ], $passParam, [$me['user_id']]));
        flash('บันทึกการเปลี่ยนแปลงเรียบร้อย');
        redirect('profile.php');
    }
    $me = array_merge($me, $f);
}

page_start('My Profile', 'profile');
?>
<form class="panel narrow" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <h2 class="pink">จัดการโปรไฟล์ส่วนตัว</h2>
  <?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

  <div class="center">
    <label class="avatar-upload">
      <?= avatar_html($me, 'xl') ?>
      <input type="file" name="profile_pic" accept="image/*" data-preview>
      <span class="small pink">เปลี่ยนรูปโปรไฟล์</span>
    </label>
    <p class="muted small">ID: <?= e($me['user_id']) ?></p>
  </div>

  <div class="grid-2">
    <label>ชื่อ - นามสกุล *<input name="user_name" value="<?= e($me['user_name']) ?>" required></label>
    <label>อีเมล (แก้ไขไม่ได้)<input value="<?= e($me['email']) ?>" disabled></label>
    <label>คณะ<input name="faculty" value="<?= e($me['faculty']) ?>" placeholder="เช่น วิศวกรรมศาสตร์"></label>
    <label>ภาควิชา / สาขา<input name="major" value="<?= e($me['major']) ?>" placeholder="เช่น IE"></label>
    <label>เบอร์โทรศัพท์<input name="phone" value="<?= e($me['phone']) ?>" placeholder="08xxxxxxxx"></label>
    <label>วันเกิด<input type="date" name="dob" value="<?= e($me['dob']) ?>"></label>
    <label>LINE ID<input name="line_id" value="<?= e($me['line_id']) ?>"></label>
    <label>Instagram<input name="ig" value="<?= e($me['ig']) ?>" placeholder="@username"></label>
  </div>
  <label>Skills <span class="muted small">(คั่นด้วยเครื่องหมาย , เช่น Python, Figma, Pitching)</span>
    <input name="skills" value="<?= e($me['skills']) ?>" data-tags>
  </label>
  <div class="tags" id="skillPreview"></div>
  <label>แนะนำตัว<textarea name="bio" rows="4" placeholder="เล่าความสนใจ ประสบการณ์ หรือสิ่งที่อยากทำ"><?= e($me['bio']) ?></textarea></label>

  <details class="sub-box">
    <summary>🔑 เปลี่ยนรหัสผ่าน (ไม่บังคับ)</summary>
    <div class="grid-2">
      <label>รหัสผ่านเดิม<input type="password" name="old_password" autocomplete="current-password"></label>
      <label>รหัสผ่านใหม่<input type="password" name="new_password" autocomplete="new-password"></label>
    </div>
  </details>

  <button class="btn btn-primary btn-block">💾 บันทึกการเปลี่ยนแปลง</button>
</form>
<?php page_end(); ?>
