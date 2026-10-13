<?php
require_once '../includes/functions.php';
require_once '../includes/layout.php';

$me = require_login();

$st = $pdo->prepare("SELECT a.*,
        (SELECT COUNT(*) FROM application ap WHERE ap.activity_id = a.act_id) AS total,
        (SELECT COUNT(*) FROM application ap WHERE ap.activity_id = a.act_id AND ap.status = 'pending') AS pending
    FROM activity a WHERE a.user_id = ? ORDER BY a.created_at DESC");
$st->execute([$me['user_id']]);
$managed = $st->fetchAll();

$st = $pdo->prepare('SELECT ap.*, a.title, a.organizer, a.act_date, a.contact
    FROM application ap JOIN activity a ON a.act_id = ap.activity_id
    WHERE ap.user_id = ? ORDER BY ap.created_at DESC');
$st->execute([$me['user_id']]);
$appliedList = $st->fetchAll();

page_start('Dashboard', 'dashboard');
?>
<h1 class="page-title">📊 Welcome, <?= e($me['user_name']) ?></h1>

<div class="stat-row">
  <div class="stat-card"><i>📣</i><div><span>Posted Activities</span><b><?= count($managed) ?></b></div></div>
  <div class="stat-card"><i>📝</i><div><span>Applied Activities</span><b><?= count($appliedList) ?></b></div></div>
  <div class="stat-card"><i>⏳</i><div><span>ผู้สมัครรอพิจารณา</span><b><?= array_sum(array_column($managed, 'pending')) ?></b></div></div>
</div>

<section class="panel">
  <h2>📌 Managed Activities <span class="muted small">(กิจกรรมที่ฉันประกาศหาคน · Recruiter)</span></h2>
  <?php if (!$managed): ?>
    <p class="empty">ยังไม่มีกิจกรรมที่โพสต์ · <a class="pink" href="create.php">สร้างกิจกรรมแรกของคุณ</a></p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Activity</th><th>Status</th><th>Applicants</th><th>Manage / Action</th></tr></thead>
    <tbody>
    <?php foreach ($managed as $a): $closed = activity_closed($a); ?>
      <tr>
        <td><b><?= e($a['title']) ?></b><br><span class="muted small"><?= e($a['category']) ?> · ปิดรับ <?= e(fmt_date($a['regis_date'])) ?></span></td>
        <td><?= $closed ? '<span class="badge badge-red">Closed</span>' : '<span class="badge badge-green">Open</span>' ?></td>
        <td><b class="pink"><?= (int)$a['total'] ?></b> Person<?= $a['pending'] ? ' <span class="badge badge-yellow">' . (int)$a['pending'] . ' รอ</span>' : '' ?></td>
        <td class="actions">
          <a class="btn btn-primary btn-sm" href="view_applicants.php?act_id=<?= (int)$a['act_id'] ?>">View Applicants</a>
          <form method="post" action="activity_action.php" class="inline">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['act_id'] ?>">
            <input type="hidden" name="action" value="toggle">
            <button class="btn btn-dark btn-sm"><?= $a['status'] === 'open' ? 'Close Entry' : 'Reopen' ?></button>
          </form>
          <a class="btn btn-light btn-sm" href="create.php?id=<?= (int)$a['act_id'] ?>">✏️ แก้ไข</a>
          <form method="post" action="activity_action.php" class="inline" data-confirm="ลบกิจกรรมนี้และใบสมัครทั้งหมด?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['act_id'] ?>">
            <input type="hidden" name="action" value="delete">
            <button class="btn btn-danger-light btn-sm">🗑</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>

<section class="panel">
  <h2>📝 Applied Activities <span class="muted small">(ประวัติการสมัครของคุณ · Applicant)</span></h2>
  <?php if (!$appliedList): ?>
    <p class="empty">ยังไม่ได้สมัครกิจกรรมใด · <a class="pink" href="home.php">ไปค้นหากิจกรรม</a></p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Activity</th><th>Organizer</th><th>ตำแหน่ง</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($appliedList as $ap): ?>
      <tr>
        <td><b><?= e($ap['title']) ?></b><br><span class="muted small">สมัครเมื่อ <?= e(date('Y-m-d H:i', strtotime($ap['created_at']))) ?></span></td>
        <td><?= e($ap['organizer']) ?></td>
        <td><?= e($ap['role']) ?></td>
        <td>
          <?= app_badge($ap['status']) ?>
          <?php if ($ap['status'] === 'rejected'): ?>
            <button type="button" class="btn btn-light btn-xs" data-toggle="#why<?= (int)$ap['id'] ?>">Why?</button>
            <div id="why<?= (int)$ap['id'] ?>" class="why" hidden>เหตุผล: <?= e($ap['reject_reason'] ?: 'ไม่ระบุ') ?></div>
          <?php elseif ($ap['status'] === 'approved' && $ap['contact']): ?>
            <div class="muted small">ติดต่อผู้จัด: <?= e($ap['contact']) ?></div>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</section>
<?php page_end(); ?>
