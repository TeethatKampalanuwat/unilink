<?php
/**
 * page_start($title, $active, $opts)
 *  $active : home | dashboard | profile | admin_dashboard | admin_users | admin_activities
 *  $opts   : ['admin' => bool, 'topbar' => string html | false]
 */
function page_start(string $title, string $active = '', array $opts = []): void
{
    $u = current_user();
    $admin = !empty($opts['admin']);
    $self = basename($_SERVER['PHP_SELF']);
    ?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · UniLink</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="<?= $admin ? 'is-admin' : '' ?>">
<div class="app">
  <aside class="sidebar">
    <?php if ($admin): ?>
      <a class="brand" href="admin_dashboard.php"><span>Uni<b>Link</b> <em>ADMIN</em></span></a>
      <nav class="menu">
        <a class="<?= $active === 'admin_dashboard' ? 'active' : '' ?>" href="admin_dashboard.php"><i>📈</i><span>Dashboard</span></a>
        <a class="<?= $active === 'admin_users' ? 'active' : '' ?>" href="admin_manage_user.php"><i>👥</i><span>Manage User</span></a>
        <a class="<?= $active === 'admin_activities' ? 'active' : '' ?>" href="admin_manage_activity.php"><i>🗓️</i><span>Manage Activity</span></a>
        <a href="home.php"><i>🏠</i><span>ดูหน้าเว็บ</span></a>
        <a class="danger" href="logout.php?admin=1"><i>↪</i><span>Logout</span></a>
      </nav>
    <?php else: ?>
      <a class="brand" href="home.php"><span class="logo">UL</span><span>Uni<b>Link</b></span></a>
      <div class="menu-label">MAIN MENU</div>
      <nav class="menu">
        <a class="<?= $active === 'home' ? 'active' : '' ?>" href="home.php"><i>🏠</i><span>Home</span></a>
        <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="dashboard.php"><i>📊</i><span>Dashboard<?= $u ? '' : ' 🔒' ?></span></a>
        <?php if ($u): ?>
          <a class="<?= $active === 'profile' ? 'active' : '' ?>" href="profile.php"><i>⚙️</i><span>My Profile</span></a>
          <a class="mobile-only" href="create.php"><i>➕</i><span>สร้าง</span></a>
          <?php if ($u['role'] === 'admin'): ?>
            <a href="admin_dashboard.php"><i>🛡️</i><span>Admin</span></a>
          <?php endif; ?>
        <?php endif; ?>
      </nav>
      <div class="user-box">
        <?php if ($u): ?>
          <?= avatar_html($u) ?>
          <div>
            <div class="user-name"><?= e($u['user_name']) ?></div>
            <div class="user-links"><a href="profile.php" class="green">Edit Profile</a> | <a href="logout.php" class="pink">Log out</a></div>
          </div>
        <?php else: ?>
          <span class="avatar">👤</span>
          <div>
            <div class="user-name">บุคคลทั่วไป</div>
            <div class="user-links"><a href="login.php?mode=login" class="pink">Log in / Register</a></div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </aside>

  <main class="main">
    <?php
    if (!$admin) {
        if (isset($opts['topbar'])) {
            if ($opts['topbar'] !== false) echo $opts['topbar'];
        } else {
            echo '<div class="topbar"><div class="topbar-spacer"></div><a class="btn btn-primary pill" href="create.php">➕ สร้างกิจกรรม</a></div>';
        }
    }
    ?>
    <div class="content">
    <?php foreach (flash() as [$type, $msg]): ?>
      <div class="alert alert-<?= e($type) ?>"><?= e($msg) ?></div>
    <?php endforeach;
}

function page_end(): void
{
    ?>
    </div>
  </main>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
<?php
}
