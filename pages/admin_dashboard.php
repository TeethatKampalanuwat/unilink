<?php
require_once '../includes/functions.php';
require_once '../includes/layout.php';

require_admin();

$c = $pdo->query("SELECT
    (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM activity) AS activities,
    (SELECT COUNT(*) FROM activity WHERE status = 'open' AND regis_date >= CURDATE()) AS open_acts,
    (SELECT COUNT(*) FROM application) AS applications")->fetch();

// ประเภทงานยอดนิยม (มีคนสมัครมากที่สุด)
$popular = $pdo->query("SELECT a.category, COUNT(ap.id) AS apps, COUNT(DISTINCT a.act_id) AS posts
    FROM activity a LEFT JOIN application ap ON ap.activity_id = a.act_id
    GROUP BY a.category ORDER BY apps DESC, posts DESC")->fetchAll();
$maxApps = max(1, ...array_map(fn($r) => (int)$r['apps'], $popular ?: [['apps' => 1]]));

$statusSum = $pdo->query('SELECT status, COUNT(*) AS n FROM application GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

$recent = $pdo->query('SELECT a.act_id, a.title, a.category, a.created_at, u.user_name
    FROM activity a JOIN users u ON u.user_id = a.user_id ORDER BY a.created_at DESC LIMIT 5')->fetchAll();

page_start('Admin Dashboard', 'admin_dashboard', ['admin' => true]);
?>
<h1 class="page-title">Real-time Dashboard</h1>

<div class="stat-row">
  <div class="stat-card accent"><div><span>จำนวนผู้ใช้ในระบบ</span><b><?= (int)$c['users'] ?></b></div><i>👥</i></div>
  <div class="stat-card accent"><div><span>กิจกรรม/งานทั้งหมด</span><b><?= (int)$c['activities'] ?></b></div><i>💼</i></div>
  <div class="stat-card accent"><div><span>เปิดรับสมัครอยู่</span><b><?= (int)$c['open_acts'] ?></b></div><i>🟢</i></div>
  <div class="stat-card accent"><div><span>ใบสมัครทั้งหมด</span><b><?= (int)$c['applications'] ?></b></div><i>📝</i></div>
</div>

<div class="grid-2 gap-lg">
  <section class="panel">
    <h2>🔥 ประเภทงานยอดนิยม (ที่มีคนสมัครมากที่สุด)</h2>
    <table>
      <thead><tr><th>หมวดหมู่</th><th>จำนวนการสมัคร</th><th>โพสต์</th></tr></thead>
      <tbody>
      <?php foreach ($popular as $r): ?>
        <tr>
          <td><b><?= CATEGORIES[$r['category']] ?? '' ?> <?= e($r['category']) ?></b></td>
          <td><div class="bar"><span style="width:<?= round($r['apps'] / $maxApps * 100) ?>%"></span></div><span class="badge badge-dark"><?= (int)$r['apps'] ?> ครั้ง</span></td>
          <td><?= (int)$r['posts'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section class="panel">
    <h2>📋 สถานะใบสมัคร</h2>
    <div class="status-sum">
      <div><?= app_badge('pending') ?><b><?= (int)($statusSum['pending'] ?? 0) ?></b></div>
      <div><?= app_badge('approved') ?><b><?= (int)($statusSum['approved'] ?? 0) ?></b></div>
      <div><?= app_badge('rejected') ?><b><?= (int)($statusSum['rejected'] ?? 0) ?></b></div>
    </div>
    <h2>🆕 กิจกรรมล่าสุด</h2>
    <ul class="contact-list">
      <?php foreach ($recent as $r): ?>
        <li><a href="admin_edit_activity.php?id=<?= (int)$r['act_id'] ?>"><b>#<?= (int)$r['act_id'] ?> <?= e($r['title']) ?></b></a>
          <span class="muted small">· <?= e($r['category']) ?> · โดย <?= e($r['user_name']) ?> · <?= e(date('Y-m-d', strtotime($r['created_at']))) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>
<?php page_end(); ?>
