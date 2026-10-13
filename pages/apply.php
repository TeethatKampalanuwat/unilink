<?php
require_once '../includes/functions.php';
require_once '../includes/layout.php';

$me = require_login();
$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM activity WHERE act_id = ?');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) { flash('ไม่พบกิจกรรม', 'error'); redirect('home.php'); }

if ($a['user_id'] === $me['user_id']) { flash('ไม่สามารถสมัครกิจกรรมที่ตัวเองโพสต์ได้', 'error'); redirect('home.php'); }
if (activity_closed($a)) { flash('กิจกรรมนี้ปิดรับสมัครแล้ว', 'error'); redirect('home.php'); }

$dup = $pdo->prepare('SELECT status FROM application WHERE user_id = ? AND activity_id = ?');
$dup->execute([$me['user_id'], $id]);
if ($dup->fetch()) { flash('คุณสมัครกิจกรรมนี้ไปแล้ว ดูสถานะได้ที่ Dashboard', 'error'); redirect('dashboard.php'); }

$questions = json_list($a['questions']);
$errors = [];
$form = ['faculty' => $me['faculty'] ?? '', 'role' => '', 'answers' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $form['faculty'] = trim($_POST['faculty'] ?? '');
    $form['role'] = trim($_POST['role'] ?? '');
    $form['answers'] = array_map('trim', (array)($_POST['answers'] ?? []));
    if ($form['faculty'] === '') $errors[] = 'กรุณากรอกคณะ';
    if ($form['role'] === '') $errors[] = 'กรุณากรอกตำแหน่ง/ฝ่ายที่ต้องการสมัคร';
    $answers = [];
    foreach ($questions as $i => $qText) {
        $ans = $form['answers'][$i] ?? '';
        if ($ans === '') $errors[] = 'กรุณาตอบคำถาม: ' . $qText;
        $answers[] = ['q' => $qText, 'a' => $ans];
    }
    if (!$errors) {
        try {
            $ins = $pdo->prepare("INSERT INTO application (user_id, activity_id, faculty, role, answers, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $ins->execute([$me['user_id'], $id, $form['faculty'], $form['role'], json_encode($answers, JSON_UNESCAPED_UNICODE)]);
            // ถ้าโปรไฟล์ยังไม่มีคณะ ให้บันทึกเก็บไว้ด้วย
            if (empty($me['faculty'])) {
                $pdo->prepare('UPDATE users SET faculty = ? WHERE user_id = ?')->execute([$form['faculty'], $me['user_id']]);
            }
            flash('สมัคร “' . $a['title'] . '” สำเร็จ! 🎉 รอผลการพิจารณาจาก Recruiter ได้ที่หน้านี้');
            redirect('dashboard.php');
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') { flash('คุณสมัครกิจกรรมนี้ไปแล้ว', 'error'); redirect('dashboard.php'); }
            throw $ex;
        }
    }
}

page_start('สมัคร ' . $a['title'], 'home');
?>
<form class="panel narrow" method="post">
  <?= csrf_field() ?>
  <div class="center">
    <?= activity_logo($a, 'lg center-block') ?>
    <h2><?= e($a['title']) ?></h2>
    <p class="muted">ผู้จัด: <?= e($a['organizer']) ?> · ปิดรับสมัคร <?= e(fmt_date($a['regis_date'])) ?></p>
  </div>
  <?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

  <label>ชื่อ-นามสกุล<input value="<?= e($me['user_name']) ?>" disabled></label>
  <label>คณะ (Faculty) *<input name="faculty" value="<?= e($form['faculty']) ?>" required></label>
  <label>ตำแหน่ง/ฝ่ายที่ต้องการสมัคร *<input name="role" value="<?= e($form['role']) ?>" placeholder="เช่น Staff, ผู้เข้าร่วม, Designer" required></label>

  <?php if ($questions): ?>
    <div class="sub-box accent">
      <b class="pink">📋 คำถามเพิ่มเติมจากผู้จัดกิจกรรม</b>
      <?php foreach ($questions as $i => $qText): ?>
        <label>คำถาม: <?= e($qText) ?> *
          <textarea name="answers[<?= $i ?>]" rows="2" placeholder="คำตอบของคุณ" required><?= e($form['answers'][$i] ?? '') ?></textarea>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p class="muted small">ระบบจะส่งข้อมูลโปรไฟล์ของคุณ (ชื่อ, คณะ, Skills, ช่องทางติดต่อ) ให้ผู้จัดกิจกรรมพิจารณา · <a href="profile.php">แก้ไขโปรไฟล์</a></p>
  <button class="btn btn-primary btn-block">ยืนยันการสมัคร 🚀</button>
  <p class="center"><a class="muted" href="home.php">ยกเลิก</a></p>
</form>
<?php page_end(); ?>
