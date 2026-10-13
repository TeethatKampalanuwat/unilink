<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/../config/db.php';

// หมวดหมู่กิจกรรม => ไอคอน
const CATEGORIES = [
    'Camp'        => '⛺',
    'Competition' => '🏆',
    'Recruitment' => '📣',
    'Tutor'       => '📚',
    'Freelance'   => '💼',
    'Club'        => '🎯',
];

const FREE_FEES = ['', 'ฟรี', 'free', 'Free', 'FREE', '0', '-'];

function e($s): string
{
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/** ป้องกัน open redirect: อนุญาตเฉพาะ path ภายในเว็บ */
function safe_next(?string $next, string $default = 'home.php'): string
{
    if (!$next || str_contains($next, '://') || str_starts_with($next, '//') || str_contains($next, "\n")) {
        return $default;
    }
    return $next;
}

function current_user(bool $refresh = false): ?array
{
    global $pdo;
    static $cache = false;
    if ($cache !== false && !$refresh) return $cache;
    if (empty($_SESSION['user_id'])) return $cache = null;
    $st = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
    $st->execute([$_SESSION['user_id']]);
    $cache = $st->fetch() ?: null;
    if (!$cache) unset($_SESSION['user_id']);
    return $cache;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('กรุณาเข้าสู่ระบบก่อนใช้งานส่วนนี้', 'error');
        redirect('login.php?mode=login&next=' . urlencode(basename($_SERVER['REQUEST_URI'])));
    }
    return $u;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_admin(): array
{
    if (!is_admin()) redirect('admin_login.php');
    return current_user();
}

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function check_csrf(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('คำขอไม่ถูกต้อง (CSRF) กรุณารีเฟรชหน้าแล้วลองใหม่');
    }
}

// ---------- Flash message ----------
function flash(?string $msg = null, string $type = 'success')
{
    if ($msg !== null) {
        $_SESSION['flash'][] = [$type, $msg];
        return null;
    }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- Helpers ----------
function initials(?string $s): string
{
    $s = trim((string)$s);
    return $s === '' ? '?' : mb_strtoupper(mb_substr($s, 0, 2));
}

function asset(?string $path): ?string
{
    return $path ? '../' . ltrim($path, '/') : null;
}

function fmt_date(?string $d): string
{
    return $d ? date('Y-m-d', strtotime($d)) : '-';
}

function is_free(?string $fee): bool
{
    return in_array(trim((string)$fee), FREE_FEES, true);
}

function activity_closed(array $a): bool
{
    return $a['status'] === 'closed' || ($a['regis_date'] && $a['regis_date'] < date('Y-m-d'));
}

function json_list(?string $json): array
{
    $v = json_decode((string)$json, true);
    return is_array($v) ? $v : [];
}

function app_badge(string $status): string
{
    $map = [
        'pending'  => ['badge-yellow', 'Pending'],
        'approved' => ['badge-green', 'Approved'],
        'rejected' => ['badge-red', 'Rejected'],
    ];
    [$cls, $label] = $map[$status] ?? ['badge-gray', $status];
    return "<span class=\"badge $cls\">$label</span>";
}

function avatar_html(?array $u, string $size = ''): string
{
    $cls = 'avatar ' . $size;
    if ($u && !empty($u['profile_pic'])) {
        return '<img class="' . $cls . '" src="' . e(asset($u['profile_pic'])) . '" alt="">';
    }
    return '<span class="' . $cls . '">' . e(initials($u['user_name'] ?? '?')) . '</span>';
}

function activity_logo(array $a, string $size = ''): string
{
    if (!empty($a['image_path'])) {
        return '<div class="act-logo ' . $size . '"><img src="' . e(asset($a['image_path'])) . '" alt=""></div>';
    }
    $color = preg_match('/^#[0-9a-fA-F]{3,8}$/', $a['theme_color'] ?? '') ? $a['theme_color'] : '#E91E8C';
    $text = $a['logo_text'] ?: initials($a['title']);
    return '<div class="act-logo ' . $size . '" style="background:' . e($color) . '">' . e($text) . '</div>';
}

/** อัปโหลดรูป คืน path แบบ relative (uploads/xxx.jpg) หรือ null ถ้าไม่ได้เลือกไฟล์ */
function upload_image(string $field, string $prefix): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('อัปโหลดรูปไม่สำเร็จ (error ' . $f['error'] . ')');
    if ($f['size'] > 5 * 1024 * 1024) throw new RuntimeException('รูปต้องมีขนาดไม่เกิน 5MB');
    $info = @getimagesize($f['tmp_name']);
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $ext = $info ? ($types[$info['mime']] ?? null) : null;
    if (!$ext) throw new RuntimeException('รองรับเฉพาะไฟล์รูป JPG, PNG, WEBP, GIF');
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) throw new RuntimeException('บันทึกไฟล์ไม่สำเร็จ');
    return 'uploads/' . $name;
}

function delete_upload(?string $path): void
{
    if ($path && str_starts_with($path, 'uploads/')) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) @unlink($full);
    }
}

/** ตรวจว่าเป็นอีเมลจุฬาฯ */
function is_chula_email(string $email): bool
{
    return (bool)preg_match('/^[^@\s]+@(student\.)?chula\.ac\.th$/i', $email);
}

/** สร้าง user_id: ใช้รหัสนิสิต 10 หลักจากอีเมลถ้ามี ไม่งั้นใช้ U + timestamp */
function new_user_id(string $email): string
{
    global $pdo;
    $local = strstr($email, '@', true);
    $candidates = [];
    if (preg_match('/^\d{10}$/', $local)) $candidates[] = $local;
    $candidates[] = 'U' . time();
    $candidates[] = 'U' . time() . random_int(10, 99);
    $st = $pdo->prepare('SELECT 1 FROM users WHERE user_id = ?');
    foreach ($candidates as $id) {
        $st->execute([$id]);
        if (!$st->fetch()) return $id;
    }
    return 'U' . bin2hex(random_bytes(5));
}
