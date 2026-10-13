<?php
require_once '../includes/functions.php';
require_once '../includes/layout.php';

$me = require_login();
$actId = (int)($_GET['act_id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM activity WHERE act_id = ?');
$st->execute([$actId]);
$a = $st->fetch();
if (!$a || ($a['user_id'] !== $me['user_id'] && $me['role'] !== 'admin')) {
    flash('ไม่พบกิจกรรม หรือคุณไม่มีสิทธิ์ดูผู้สมัคร', 'error');
    redirect('dashboard.php');
}

$st = $pdo->prepare('SELECT ap.*, u.user_name, u.email, u.phone, u.line_id, u.ig, u.skills, u.bio, u.major, u.profile_pic
    FROM application ap JOIN users u ON u.user_id = ap.user_id
    WHERE ap.activity_id = ? ORDER BY ap.created_at ASC');
$st->execute([$actId]);
$groups = ['pending' => [], 'approved' => [], 'rejected' => []];
foreach ($st->fetchAll() as $row) $groups[$row['status']][] = $row;
$total = array_sum(array_map('count', $groups));

$labels = [
    'pending'  => ['🟡', 'Pending Review', 'ยังไม่มีผู้สมัครที่รอพิจารณา'],
    'approved' => ['🟢', 'Approved', 'No approved applicants'],
    'rejected' => ['🔴', 'Rejected', 'No rejected applicants'],
];

page_start('ผู้สมัคร ' . $a['title'], 'dashboard');
?>
<a class="btn btn-primary btn-sm" href="dashboard.php">← Back to Dashboard</a>

<div class="panel">
  <h2>Manage Activity: <span class="pink"><?= e($a['title']) ?></span></h2>
  <p class="muted">👥 Number of Applicants: <b><?= $total ?></b> person · ✅ <?= count($groups['approved']) ?> · ⏳ <?= count($groups['pending']) ?> · ❌ <?= count($groups['rejected']) ?></p>
</div>

<?php foreach ($groups as $status => $list): [$dot, $label, $emptyText] = $labels[$status]; ?>
  <h3 class="group-title status-<?= $status ?>"><?= $dot ?> <?= $label ?> (<?= count($list) ?>)</h3>
  <?php if (!$list): ?><div class="panel empty"><?= $emptyText ?></div><?php endif; ?>
  <?php foreach ($list as $ap): $answers = json_list($ap['answers']); ?>
    <div class="applicant status-<?= $status ?>">
      <div class="applicant-row">
        <?= avatar_html($ap) ?>
        <div class="grow">
          <b><?= e($ap['user_name']) ?></b> <span class="tag small"><?= e(mb_strtoupper($ap['faculty'])) ?></span>
          <div class="muted small">🎯 Position: <?= e($ap['role']) ?> · สมัครเมื่อ <?= e(date('Y-m-d H:i', strtotime($ap['created_at']))) ?></div>
        </div>
        <div class="applicant-actions">
          <button type="button" class="btn btn-light-blue btn-sm" data-toggle="#detail<?= (int)$ap['id'] ?>">📄 Details</button>
          <?php if ($status !== 'approved'): ?>
            <form method="post" action="update_application.php" class="inline">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ap['id'] ?>"><input type="hidden" name="action" value="approve">
              <button class="btn btn-success-light btn-sm">✓ accept</button>
            </form>
          <?php endif; ?>
          <?php if ($status !== 'rejected'): ?>
            <button type="button" class="btn btn-danger-light btn-sm" data-toggle="#reject<?= (int)$ap['id'] ?>">✕ decline</button>
          <?php endif; ?>
        </div>
      </div>

      <form id="reject<?= (int)$ap['id'] ?>" class="reject-form" method="post" action="update_application.php" hidden>
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ap['id'] ?>"><input type="hidden" name="action" value="reject">
        <label>เหตุผลที่ไม่ผ่านการคัดเลือก (ผู้สมัครจะเห็นข้อความนี้)
          <textarea name="reason" rows="2" placeholder="เช่น ขออภัย ตำแหน่งนี้เต็มแล้ว ขอบคุณที่สนใจนะครับ">ขอบคุณที่สนใจ แต่รอบนี้เรายังไม่สามารถรับได้</textarea>
        </label>
        <button class="btn btn-danger btn-sm">ยืนยันการปฏิเสธ</button>
      </form>

      <div id="detail<?= (int)$ap['id'] ?>" class="applicant-detail" hidden>
        <div class="grid-2">
          <div>
            <h4>ข้อมูลผู้สมัคร</h4>
            <ul class="contact-list">
              <li>📧 <?= e($ap['email']) ?></li>
              <?php if ($ap['phone']): ?><li>📞 <?= e($ap['phone']) ?></li><?php endif; ?>
              <?php if ($ap['line_id']): ?><li>💬 LINE: <?= e($ap['line_id']) ?></li><?php endif; ?>
              <?php if ($ap['ig']): ?><li>📷 IG: <?= e($ap['ig']) ?></li><?php endif; ?>
              <?php if ($ap['major']): ?><li>🎓 <?= e($ap['major']) ?></li><?php endif; ?>
            </ul>
            <?php if ($ap['skills']): ?><div class="tags"><?php foreach (array_filter(array_map('trim', explode(',', $ap['skills']))) as $s): ?><span class="tag"><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
            <?php if ($ap['bio']): ?><p class="small"><?= nl2br(e($ap['bio'])) ?></p><?php endif; ?>
            <a class="pink small" href="profile.php?id=<?= urlencode($ap['user_id']) ?>">ดูโปรไฟล์เต็ม →</a>
          </div>
          <div>
            <h4>คำตอบคำถามคัดกรอง</h4>
            <?php if (!$answers): ?><p class="muted small">ไม่มีคำถาม</p><?php endif; ?>
            <?php foreach ($answers as $qa): ?>
              <p class="small"><b>Q: <?= e($qa['q'] ?? '') ?></b><br>A: <?= nl2br(e($qa['a'] ?? '')) ?></p>
            <?php endforeach; ?>
            <?php if ($status === 'rejected' && $ap['reject_reason']): ?>
              <p class="small muted">เหตุผลที่ปฏิเสธ: <?= e($ap['reject_reason']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>
<?php page_end(); ?>
