<?php
require_once '../includes/functions.php';
require_once '../includes/layout.php';

require_admin();

$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? '';
$where = ['1=1'];
$p = [];
if ($q !== '') { $where[] = '(u.user_id LIKE ? OR u.user_name LIKE ? OR u.email LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
if (in_array($role, ['admin', 'user', 'guest'], true)) { $where[] = 'u.role = ?'; $p[] = $role; }

$st = $pdo->prepare('SELECT u.*,
    (SELECT COUNT(*) FROM activity a WHERE a.user_id = u.user_id) AS posts,
    (SELECT COUNT(*) FROM application ap WHERE ap.user_id = u.user_id) AS apps
    FROM users u WHERE ' . implode(' AND ', $where) . " ORDER BY u.role = 'admin' DESC, u.created_at DESC");
$st->execute($p);
$users = $st->fetchAll();

page_start('Manage Users', 'admin_users', ['admin' => true]);
?>
<h1 class="page-title">Manage User Details</h1>
<section class="panel">
  <form class="toolbar" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหา ID / ชื่อ / อีเมล">
    <select name="role">
      <option value="">ทุก Role</option>
      <?php foreach (['admin', 'user', 'guest'] as $r): ?><option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm">ค้นหา</button>
  </form>
  <h2>☰ รายชื่อผู้ใช้ทั้งหมดในระบบ (<?= count($users) ?>)</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>User ID</th><th>ชื่อผู้ใช้งาน</th><th>อีเมล</th><th>Role</th><th>โพสต์ / สมัคร</th><th>การจัดการ</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><span class="code"><?= e($u['user_id']) ?></span></td>
        <td><b><?= e($u['user_name']) ?></b></td>
        <td><?= e($u['email']) ?></td>
        <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-pink' : ($u['role'] === 'guest' ? 'badge-gray' : 'badge-blue') ?>"><?= e($u['role']) ?></span></td>
        <td><?= (int)$u['posts'] ?> / <?= (int)$u['apps'] ?></td>
        <td><a class="btn btn-warning btn-sm" href="admin_edit_user.php?id=<?= urlencode($u['user_id']) ?>">👤 แก้ไขข้อมูล</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<?php page_end(); ?>
