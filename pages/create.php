<?php
/**
 * สร้าง / แก้ไขกิจกรรม
 *  - create.php            : สร้างโพสต์ใหม่
 *  - create.php?id=XX      : เจ้าของโพสต์แก้ไข
 *  - admin_edit_activity.php?id=XX : include ไฟล์นี้โดยตั้ง $ADMIN_CONTEXT = true
 */
require_once '../includes/functions.php';
require_once '../includes/layout.php';

$ADMIN_CONTEXT = $ADMIN_CONTEXT ?? false;
$me = $ADMIN_CONTEXT ? require_admin() : require_login();
$backUrl = $ADMIN_CONTEXT ? 'admin_manage_activity.php' : 'dashboard.php';

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$a = [
    'title' => '', 'category' => '', 'organizer' => '', 'logo_text' => '', 'theme_color' => '#E91E8C',
    'regis_date' => '', 'act_date' => '', 'fee' => 'ฟรี', 'benefits' => '', 'description' => '',
    'questions' => '[]', 'contact' => '', 'image_path' => null, 'status' => 'open',
];
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM activity WHERE act_id = ?');
    $st->execute([$editId]);
    $a = $st->fetch();
    if (!$a || ($a['user_id'] !== $me['user_id'] && $me['role'] !== 'admin')) {
        flash('ไม่พบกิจกรรม หรือคุณไม่มีสิทธิ์แก้ไข', 'error');
        redirect($backUrl);
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    foreach (['title', 'category', 'organizer', 'logo_text', 'theme_color', 'regis_date', 'act_date', 'fee', 'benefits', 'description', 'contact', 'status'] as $k) {
        $a[$k] = trim($_POST[$k] ?? ($a[$k] ?? ''));
    }
    $questions = array_values(array_filter(array_map('trim', (array)($_POST['questions'] ?? [])), 'strlen'));
    $a['questions'] = json_encode($questions, JSON_UNESCAPED_UNICODE);

    if ($a['title'] === '') $errors[] = 'กรุณากรอกชื่อกิจกรรม';
    if (!isset(CATEGORIES[$a['category']])) $errors[] = 'กรุณาเลือกหมวดหมู่';
    if ($a['organizer'] === '') $errors[] = 'กรุณากรอกผู้จัดกิจกรรม';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $a['regis_date'])) $errors[] = 'กรุณาเลือกวันปิดรับสมัคร';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $a['act_date'])) $errors[] = 'กรุณาเลือกวันที่จัดกิจกรรม';
    if (!$errors && $a['act_date'] < $a['regis_date']) $errors[] = 'วันที่จัดกิจกรรมต้องไม่ก่อนวันปิดรับสมัคร';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $a['theme_color'])) $a['theme_color'] = '#E91E8C';
    if ($a['fee'] === '') $a['fee'] = 'ฟรี';
    if (!in_array($a['status'], ['open', 'closed'], true)) $a['status'] = 'open';
    $a['logo_text'] = mb_substr($a['logo_text'], 0, 6);

    $newImg = null;
    if (!$errors) {
        try { $newImg = upload_image('image', 'act'); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }
    if (!empty($_POST['remove_image']) && !$newImg) {
        delete_upload($a['image_path']);
        $a['image_path'] = null;
    }
    if ($newImg) {
        delete_upload($a['image_path']);
        $a['image_path'] = $newImg;
    }

    if (!$errors) {
        $vals = [$a['title'], $a['category'], $a['organizer'], $a['logo_text'] ?: null, $a['theme_color'],
                 $a['regis_date'], $a['act_date'], $a['fee'], $a['benefits'] ?: null, $a['description'] ?: null,
                 $a['questions'], $a['contact'] ?: null, $a['image_path'], $a['status']];
        if ($editId) {
            $st = $pdo->prepare('UPDATE activity SET title=?, category=?, organizer=?, logo_text=?, theme_color=?, regis_date=?, act_date=?,
                                 fee=?, benefits=?, description=?, questions=?, contact=?, image_path=?, status=? WHERE act_id=?');
            $st->execute([...$vals, $editId]);
            flash('บันทึกการแก้ไขกิจกรรมเรียบร้อย');
        } else {
            $st = $pdo->prepare('INSERT INTO activity (title, category, organizer, logo_text, theme_color, regis_date, act_date,
                                 fee, benefits, description, questions, contact, image_path, status, user_id)
                                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([...$vals, $me['user_id']]);
            flash('โพสต์กิจกรรมเรียบร้อย! ดูผู้สมัครได้ที่หน้า Dashboard');
        }
        redirect($backUrl);
    }
}

$questions = json_list($a['questions']);
$title = $editId ? 'แก้ไขข้อมูลกิจกรรม' : 'สร้างกิจกรรมใหม่';
page_start($title, $ADMIN_CONTEXT ? 'admin_activities' : '', ['admin' => $ADMIN_CONTEXT]);
?>
<form class="panel narrow" method="post" enctype="multipart/form-data" id="activityForm">
  <?= csrf_field() ?>
  <h2>➕ <?= e($title) ?> <?= $editId ? '<span class="tag">ID #' . $editId . '</span>' : '(Post Activity)' ?></h2>
  <p class="muted small">ช่องที่มี <b class="pink">*</b> จำเป็นต้องกรอก · ด้านขวาคือตัวอย่างการ์ดที่จะแสดงบนหน้าหลัก</p>
  <?php if ($errors): ?><div class="alert alert-error"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

  <div class="create-layout">
    <div>
      <fieldset>
        <legend><span class="step">1</span> ข้อมูลหลัก</legend>
        <label>ชื่อกิจกรรม <b class="pink">*</b><input name="title" value="<?= e($a['title']) ?>" placeholder="เช่น ICC 2026, ค่ายฟันเฟืองสานฝัน" required data-preview-field="title"></label>
        <div class="grid-2">
          <label>หมวดหมู่ <b class="pink">*</b>
            <select name="category" required data-preview-field="category">
              <option value="">-- เลือกหมวดหมู่ --</option>
              <?php foreach (CATEGORIES as $c => $icon): ?>
                <option value="<?= e($c) ?>" <?= $a['category'] === $c ? 'selected' : '' ?>><?= $icon ?> <?= e($c) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>ผู้จัดกิจกรรม <b class="pink">*</b><input name="organizer" value="<?= e($a['organizer']) ?>" placeholder="เช่น คณะวิศวกรรมศาสตร์" required data-preview-field="organizer"></label>
        </div>
      </fieldset>

      <fieldset>
        <legend><span class="step">2</span> วันที่</legend>
        <div class="grid-2">
          <label>วันปิดรับสมัคร <b class="pink">*</b><input type="date" name="regis_date" value="<?= e($a['regis_date']) ?>" required data-preview-field="regis"></label>
          <label>วันที่จัดกิจกรรม <b class="pink">*</b><input type="date" name="act_date" value="<?= e($a['act_date']) ?>" required data-preview-field="act"></label>
        </div>
      </fieldset>

      <fieldset>
        <legend><span class="step">3</span> รายละเอียด</legend>
        <div class="grid-2">
          <label>ค่าธรรมเนียม (Entry Fee)<input name="fee" value="<?= e($a['fee']) ?>" placeholder="เช่น ฟรี, 400 THB / Team" data-preview-field="fee"></label>
          <label>สิ่งที่ได้รับ (Benefits)<input name="benefits" value="<?= e($a['benefits']) ?>" placeholder="เช่น เกียรติบัตร, ชั่วโมงกิจกรรม"></label>
        </div>
        <label>ช่องทางติดต่อ (Contact)<input name="contact" value="<?= e($a['contact']) ?>" placeholder="เช่น Line ID, เบอร์โทรศัพท์"></label>
        <label>รายละเอียดเพิ่มเติม (Description)<textarea name="description" rows="4" placeholder="อธิบายเกี่ยวกับกิจกรรม คุณสมบัติที่ต้องการ ตำแหน่งที่เปิดรับ..."><?= e($a['description']) ?></textarea></label>
      </fieldset>

      <fieldset>
        <legend><span class="step">4</span> หน้าตาการ์ด (ไม่บังคับ)</legend>
        <div class="grid-2">
          <label>ตัวอักษรโลโก้ (สูงสุด 6 ตัว)<input name="logo_text" value="<?= e($a['logo_text']) ?>" maxlength="6" placeholder="เช่น ICC" data-preview-field="logo"></label>
          <label>สีธีม<input type="color" name="theme_color" value="<?= e($a['theme_color']) ?>" data-preview-field="color"></label>
        </div>
        <label>รูปหน้าปก (JPG/PNG ≤ 5MB)<input type="file" name="image" accept="image/*" data-preview-field="image"></label>
        <?php if ($a['image_path']): ?>
          <label class="check"><input type="checkbox" name="remove_image" value="1"> ลบรูปปัจจุบัน</label>
        <?php endif; ?>
        <?php if ($editId): ?>
          <label>สถานะ
            <select name="status">
              <option value="open" <?= $a['status'] === 'open' ? 'selected' : '' ?>>Open (เปิดรับสมัคร)</option>
              <option value="closed" <?= $a['status'] === 'closed' ? 'selected' : '' ?>>Closed (ปิดรับสมัคร)</option>
            </select>
          </label>
        <?php endif; ?>
      </fieldset>

      <fieldset class="sub-box">
        <legend><span class="step">5</span> 💬 คำถามคัดกรองผู้สมัคร (ไม่บังคับ)</legend>
        <p class="muted small">เพิ่มคำถามให้ผู้สมัครตอบตอนกดสมัคร เช่น “ทำไมถึงอยากเข้าร่วมงานนี้?”</p>
        <div id="questionList">
          <?php foreach ($questions as $qText): ?>
            <div class="q-row"><input name="questions[]" value="<?= e($qText) ?>"><button type="button" class="btn btn-light btn-sm" data-remove-q>✕</button></div>
          <?php endforeach; ?>
        </div>
        <div class="q-row">
          <input id="newQuestion" placeholder="ใส่คำถามของคุณ แล้วกด + หรือ Enter">
          <button type="button" class="btn btn-primary btn-sm" id="addQuestion">+</button>
        </div>
      </fieldset>
    </div>

    <aside class="preview-col">
      <div class="muted small">ตัวอย่างการ์ด</div>
      <article class="act-card" id="cardPreview">
        <header><h3 data-p="title"><?= e($a['title'] ?: 'ชื่อกิจกรรม') ?></h3><span class="muted small" data-p="category"><?= e($a['category'] ?: 'หมวดหมู่') ?></span></header>
        <div class="act-body">
          <div class="act-logo" data-p="logo" style="background:<?= e($a['theme_color']) ?>"><?php if ($a['image_path']): ?><img src="<?= e(asset($a['image_path'])) ?>" alt=""><?php else: ?><?= e($a['logo_text'] ?: 'UL') ?><?php endif; ?></div>
          <dl>
            <dt>Registration</dt><dd data-p="regis"><?= e($a['regis_date'] ?: '-') ?></dd>
            <dt>Entry Fee</dt><dd data-p="fee"><?= e($a['fee']) ?></dd>
            <dt>Organizer</dt><dd data-p="organizer"><?= e($a['organizer'] ?: '-') ?></dd>
          </dl>
        </div>
        <footer><span class="muted small">Activity Date: <b data-p="act"><?= e($a['act_date'] ?: '-') ?></b></span><span class="btn btn-primary pill">APPLY</span></footer>
      </article>
    </aside>
  </div>

  <button class="btn btn-primary btn-block"><?= $editId ? '💾 บันทึกการแก้ไข' : 'โพสต์กิจกรรมเลย 🚀' ?></button>
  <p class="center"><a class="muted" href="<?= e($backUrl) ?>">ยกเลิก</a></p>
</form>
<?php page_end(); ?>
