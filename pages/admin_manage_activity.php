<?php
require_once '../includes/functions.php';
require_once '../includes/layout.php';

require_admin();

$q = trim($_GET['q'] ?? '');
$cat = $_GET['cat'] ?? '';
$where = ['1=1'];
$p = [];
if ($q !== '') { $where[] = '(a.title LIKE ? OR a.organizer LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
if (isset(CATEGORIES[$cat])) { $where[] = 'a.category = ?'; $p[] = $cat; }

$st = $pdo->prepare('SELECT a.*, u.user_name,
    (SELECT COUNT(*) FROM application ap WHERE ap.activity_id = a.act_id) AS apps
    FROM activity a JOIN users u ON u.user_id = a.user_id
    WHERE ' . implode(' AND ', $where) . ' ORDER BY a.act_id DESC');
$st->execute($p);
$acts = $st->fetchAll();

page_start('Manage Activity', 'admin_activities', ['admin' => true]);
?>
<h1 class="page-title">🗓️ Manage Activity</h1>
<section class="panel">
  <form class="toolbar" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหาชื่อกิจกรรม / ผู้จัด">
    <select name="cat">
      <option value="">ทุกหมวดหมู่</option>
      <?php foreach (CATEGORIES as $c => $i): ?><option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm">ค้นหา</button>
  </form>
  <div class="table-wrap"><table>
    <thead><tr><th>ID</th><th>ชื่อกิจกรรม</th><th>หมวดหมู่</th><th>ผู้จัดกิจกรรม</th><th>วันที่จัดงาน</th><th>สถานะ</th><th>ผู้สมัคร</th><th>การจัดการ</th></tr></thead>
    <tbody>
    <?php foreach ($acts as $a): ?>
      <tr>
        <td><b class="pink">#<?= (int)$a['act_id'] ?></b></td>
        <td><b><?= e($a['title']) ?></b><br><span class="muted small">โพสต์โดย <?= e($a['user_name']) ?></span></td>
        <td><span class="badge badge-dark"><?= e($a['category']) ?></span></td>
        <td><?= e($a['organizer']) ?></td>
        <td>🕓 <?= e(fmt_date($a['act_date'])) ?></td>
        <td><?= activity_closed($a) ? '<span class="badge badge-red">Closed</span>' : '<span class="badge badge-green">Open</span>' ?></td>
        <td><a href="view_applicants.php?act_id=<?= (int)$a['act_id'] ?>"><?= (int)$a['apps'] ?> คน</a></td>
        <td class="actions">
          <a class="btn btn-pink-light btn-sm" href="admin_edit_activity.php?id=<?= (int)$a['act_id'] ?>">✏️ แก้ไข</a>
          <form method="post" action="activity_action.php" class="inline" data-confirm="ลบกิจกรรม #<?= (int)$a['act_id'] ?> และใบสมัครทั้งหมด?">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$a['act_id'] ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="back" value="admin_manage_activity.php">
            <button class="btn btn-danger-light btn-sm">🗑 ลบ</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$acts): ?><tr><td colspan="8" class="empty">ไม่พบกิจกรรม</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</section>
<?php page_end(); ?>
