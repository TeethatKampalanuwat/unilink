<?php
require_once '../includes/functions.php';
require_once '../includes/layout.php';

$u = current_user();

// ----- รับค่าค้นหา / filter -----
$q     = trim($_GET['q'] ?? '');
$cat   = $_GET['cat'] ?? '';
$fee   = $_GET['fee'] ?? '';
$month = $_GET['month'] ?? '';
$org   = trim($_GET['org'] ?? '');
$open  = !empty($_GET['open']);

$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(a.title LIKE ? OR a.organizer LIKE ? OR a.description LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if (isset(CATEGORIES[$cat])) {
    $where[] = 'a.category = ?';
    $params[] = $cat;
}
$freeList = implode(',', array_fill(0, count(FREE_FEES), '?'));
if ($fee === 'free') {
    $where[] = "TRIM(a.fee) IN ($freeList)";
    array_push($params, ...FREE_FEES);
} elseif ($fee === 'paid') {
    $where[] = "TRIM(a.fee) NOT IN ($freeList)";
    array_push($params, ...FREE_FEES);
}
if (preg_match('/^\d{4}-\d{2}$/', $month)) {
    $where[] = "DATE_FORMAT(a.act_date, '%Y-%m') = ?";
    $params[] = $month;
}
if ($org !== '') {
    $where[] = 'a.organizer LIKE ?';
    $params[] = "%$org%";
}
if ($open) {
    $where[] = "a.status = 'open' AND a.regis_date >= CURDATE()";
}

$sql = "SELECT a.*, u.user_name AS poster,
               (SELECT COUNT(*) FROM application ap WHERE ap.activity_id = a.act_id) AS applicants
        FROM activity a JOIN users u ON u.user_id = a.user_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY (a.status = 'open' AND a.regis_date >= CURDATE()) DESC, a.created_at DESC";
$st = $pdo->prepare($sql);
$st->execute($params);
$activities = $st->fetchAll();

// สถิติ
$stats = $pdo->query("SELECT
    (SELECT COUNT(*) FROM activity) AS posts,
    (SELECT COUNT(*) FROM users WHERE role = 'user') AS students,
    (SELECT COUNT(*) FROM activity WHERE category = 'Club') AS clubs")->fetch();

// กิจกรรมที่ผู้ใช้สมัครแล้ว
$applied = [];
if ($u) {
    $s = $pdo->prepare('SELECT activity_id, status FROM application WHERE user_id = ?');
    $s->execute([$u['user_id']]);
    $applied = $s->fetchAll(PDO::FETCH_KEY_PAIR);
}

$titles = $pdo->query('SELECT DISTINCT title FROM activity ORDER BY created_at DESC LIMIT 200')->fetchAll(PDO::FETCH_COLUMN);
$filterCount = (int)($fee !== '') + (int)($month !== '') + (int)($org !== '') + (int)$open;

// ลิงก์หมวดหมู่ (คงค่าค้นหาเดิม)
function cat_link(string $c): string
{
    $p = $_GET;
    unset($p['cat']);
    if ($c !== '') $p['cat'] = $c;
    return 'home.php' . ($p ? '?' . http_build_query($p) : '');
}

ob_start(); ?>
<form class="topbar" method="get" action="home.php" id="searchForm">
  <div class="search">
    <span>🔍</span>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหากิจกรรม แล้วกด Enter..." list="titleList" autocomplete="off">
    <datalist id="titleList"><?php foreach ($titles as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?></datalist>
  </div>
  <div class="filter-wrap">
    <button type="button" class="btn btn-ghost pill" data-toggle="#filterPanel">⚙ Filter<?= $filterCount ? " ($filterCount)" : '' ?></button>
    <div class="filter-panel" id="filterPanel" hidden>
      <label>🗂 Category
        <select name="cat">
          <option value="">ทั้งหมด</option>
          <?php foreach (CATEGORIES as $c => $icon): ?>
            <option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= $icon ?> <?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>💰 Entry Fee
        <select name="fee">
          <option value="">ทั้งหมด</option>
          <option value="free" <?= $fee === 'free' ? 'selected' : '' ?>>ฟรี</option>
          <option value="paid" <?= $fee === 'paid' ? 'selected' : '' ?>>มีค่าใช้จ่าย</option>
        </select>
      </label>
      <label>📅 เดือนที่จัดกิจกรรม
        <input type="month" name="month" value="<?= e($month) ?>">
      </label>
      <label>🏢 ผู้จัดกิจกรรม
        <input type="text" name="org" value="<?= e($org) ?>" placeholder="เช่น คณะวิศวกรรมศาสตร์">
      </label>
      <label class="check"><input type="checkbox" name="open" value="1" <?= $open ? 'checked' : '' ?>> แสดงเฉพาะที่ยังเปิดรับสมัคร</label>
      <div class="row-btns">
        <a class="btn btn-light" href="home.php">ล้าง</a>
        <button class="btn btn-primary">Apply</button>
      </div>
    </div>
  </div>
  <div class="topbar-spacer"></div>
  <a class="btn btn-primary pill" href="create.php">➕ สร้างกิจกรรม</a>
</form>
<?php
$topbar = ob_get_clean();
page_start('Home', 'home', ['topbar' => $topbar]);
?>

<section class="hero">
  <h1>UniLink</h1>
  <p>Your gateway to university activities at Chulalongkorn University</p>
  <div class="hero-stats">
    <div><b><?= (int)$stats['posts'] ?></b><span>Posts</span></div>
    <div><b><?= (int)$stats['students'] ?></b><span>Students</span></div>
    <div><b><?= (int)$stats['clubs'] ?></b><span>Clubs</span></div>
  </div>
</section>

<nav class="cats">
  <a class="cat <?= $cat === '' ? 'active' : '' ?>" href="<?= e(cat_link('')) ?>"><i>✨</i><span>All</span></a>
  <?php foreach (CATEGORIES as $c => $icon): ?>
    <a class="cat <?= $cat === $c ? 'active' : '' ?>" href="<?= e(cat_link($c)) ?>"><i><?= $icon ?></i><span><?= e($c) ?></span></a>
  <?php endforeach; ?>
</nav>

<?php if ($q !== '' || $cat !== '' || $filterCount): ?>
  <p class="result-info">พบ <b><?= count($activities) ?></b> กิจกรรม
    <?php if ($q !== ''): ?>สำหรับ “<?= e($q) ?>”<?php endif; ?>
    · <a href="home.php">ล้างการค้นหา</a></p>
<?php endif; ?>

<?php if (!$activities): ?>
  <div class="empty">ยังไม่พบกิจกรรมที่ตรงกับเงื่อนไข 😢<br><a href="home.php">ดูกิจกรรมทั้งหมด</a></div>
<?php endif; ?>

<div class="card-grid">
<?php foreach ($activities as $a):
    $closed = activity_closed($a);
    $isOwner = $u && $u['user_id'] === $a['user_id'];
    $appStatus = $applied[$a['act_id']] ?? null;

    if ($isOwner)          { $btn = ['โพสต์ของคุณ', 'dashboard.php', 'btn-light']; }
    elseif ($appStatus)    { $btn = ['สมัครแล้ว', 'dashboard.php', 'btn-light']; }
    elseif ($closed)       { $btn = ['ปิดรับสมัคร', null, 'btn-disabled']; }
    elseif (!$u)           { $btn = ['APPLY', 'login.php?mode=login&next=' . urlencode('apply.php?id=' . $a['act_id']), 'btn-primary']; }
    else                   { $btn = ['APPLY', 'apply.php?id=' . $a['act_id'], 'btn-primary']; }

    $modal = [
        'title' => $a['title'], 'category' => $a['category'],
        'regis' => fmt_date($a['regis_date']), 'act' => fmt_date($a['act_date']),
        'fee' => $a['fee'], 'organizer' => $a['organizer'], 'benefits' => $a['benefits'],
        'contact' => $a['contact'], 'description' => $a['description'],
        'image' => asset($a['image_path']), 'poster' => $a['poster'],
        'applicants' => (int)$a['applicants'],
        'btnText' => $btn[0], 'btnHref' => $btn[1], 'btnClass' => $btn[2], 'closed' => $closed,
    ];
?>
  <article class="act-card <?= $closed ? 'is-closed' : '' ?>" data-activity="<?= e(json_encode($modal, JSON_UNESCAPED_UNICODE)) ?>" tabindex="0">
    <header>
      <h3><?= e($a['title']) ?></h3>
      <span class="muted small"><?= CATEGORIES[$a['category']] ?? '' ?> <?= e($a['category']) ?></span>
      <?php if ($closed): ?><span class="badge badge-gray float-right">Closed</span><?php endif; ?>
    </header>
    <div class="act-body">
      <?= activity_logo($a) ?>
      <dl>
        <dt>Registration</dt><dd><?= e(fmt_date($a['regis_date'])) ?></dd>
        <dt>Entry Fee</dt><dd><?= e($a['fee']) ?></dd>
        <dt>Organizer</dt><dd><?= e($a['organizer']) ?></dd>
      </dl>
    </div>
    <footer>
      <span class="muted small">Activity Date: <b><?= e(fmt_date($a['act_date'])) ?></b></span>
      <?php if ($btn[1]): ?>
        <a class="btn pill <?= $btn[2] ?>" href="<?= e($btn[1]) ?>"><?= e($btn[0]) ?></a>
      <?php else: ?>
        <span class="btn pill <?= $btn[2] ?>"><?= e($btn[0]) ?></span>
      <?php endif; ?>
    </footer>
  </article>
<?php endforeach; ?>
</div>

<!-- Modal รายละเอียดกิจกรรม -->
<div class="modal" id="actModal" hidden>
  <div class="modal-box">
    <button class="modal-close" data-close aria-label="ปิด">×</button>
    <img class="modal-img" alt="" hidden>
    <h2 data-f="title"></h2>
    <span class="tag" data-f="category"></span>
    <div class="info-grid">
      <div>📌 <b>ปิดรับสมัคร:</b> <span data-f="regis"></span></div>
      <div>📅 <b>วันที่จัด:</b> <span data-f="act"></span></div>
      <div>💰 <b>ค่าธรรมเนียม:</b> <span data-f="fee"></span></div>
      <div>🏢 <b>ผู้จัดกิจกรรม:</b> <span data-f="organizer"></span></div>
      <div>🎁 <b>สิ่งที่ได้รับ:</b> <span data-f="benefits"></span></div>
      <div>📞 <b>ติดต่อ:</b> <span data-f="contact"></span></div>
    </div>
    <div class="desc-box">
      <b>📄 รายละเอียดเพิ่มเติม</b>
      <p data-f="description"></p>
      <p class="muted small">โพสต์โดย <span data-f="poster"></span> · ผู้สมัครแล้ว <span data-f="applicants"></span> คน</p>
    </div>
    <a class="btn btn-primary btn-block" data-f="btn">APPLY NOW 🚀</a>
  </div>
</div>

<?php page_end(); ?>
