<?php
/**
 * ALMuhalab Content Management Admin Panel
 * Single-file admin for managing landing page JSON content
 * Version: 2.0.0 — Supports AR/EN Bilingual, Divisions, Team, Services, Theme
 * Structure: home.json has separate "en" and "ar" sections + "theme"
 */

session_start();

// ─── Configuration ───────────────────────────────────────────────
define('ADMIN_PASSWORD', 'almuhalab2026');
define('CONTENT_DIR', dirname(__DIR__) . '/content');
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('SITE_ROOT', dirname(__DIR__));

// Ensure uploads directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// ─── Ensure content directory exists ─────────────────────────────
if (!is_dir(CONTENT_DIR)) {
    mkdir(CONTENT_DIR, 0755, true);
}

// ─── Helper Functions ────────────────────────────────────────────
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function getPageFiles() {
    $pages = [];
    if (is_dir(CONTENT_DIR)) {
        $files = glob(CONTENT_DIR . '/*.json');
        foreach ($files as $file) {
            $name = basename($file, '.json');
            $data = json_decode(file_get_contents($file), true);
            $pages[] = [
                'name' => $name,
                'file' => $file,
                'title' => $data['en']['site']['name'] ?? $data['site']['name'] ?? $name,
                'modified' => filemtime($file)
            ];
        }
    }
    usort($pages, fn($a, $b) => $b['modified'] - $a['modified']);
    return $pages;
}

function loadPage($name) {
    $file = CONTENT_DIR . '/' . basename($name) . '.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

function savePage($name, $data) {
    $file = CONTENT_DIR . '/' . basename($name) . '.json';
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function deletePage($name) {
    $file = CONTENT_DIR . '/' . basename($name) . '.json';
    if (file_exists($file)) {
        return unlink($file);
    }
    return false;
}

function sanitize($input) {
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function setFlash($key, $value) {
    $_SESSION['flash'][$key] = $value;
}

function getLang() {
    return $_SESSION['admin_lang'] ?? 'ar';
}

function setLang($lang) {
    $_SESSION['admin_lang'] = in_array($lang, ['ar', 'en']) ? $lang : 'ar';
}

// ─── Handle Actions ──────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$pageName = $_GET['page'] ?? $_POST['page'] ?? '';
$lang = getLang();

// Language toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'setlang') {
    setLang($_POST['lang'] ?? 'ar');
    $referer = $_SERVER['HTTP_REFERER'] ?? '?action=dashboard';
    header('Location: ' . $referer);
    exit;
}

// Login handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        setFlash('success', 'مرحباً بعودتك! / Welcome back!');
        header('Location: ?action=dashboard');
        exit;
    } else {
        setFlash('error', 'كلمة مرور غير صحيحة / Invalid password');
        header('Location: ?action=login');
        exit;
    }
}

// Logout
if ($action === 'logout') {
    session_destroy();
    header('Location: ?action=login');
    exit;
}

// Save page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save' && isLoggedIn()) {
    $pageName = $_POST['page'] ?? '';
    if (empty($pageName)) {
        setFlash('error', 'Invalid page name');
        header('Location: ?action=dashboard');
        exit;
    }

    $data = $_POST['content'] ?? '{}';
    $decoded = json_decode($data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        setFlash('error', 'Invalid JSON: ' . json_last_error_msg());
        header("Location: ?action=edit&page=" . urlencode($pageName));
        exit;
    }

    if (savePage($pageName, $decoded)) {
        setFlash('success', 'Page "' . $pageName . '" saved successfully');
    } else {
        setFlash('error', 'Failed to save page');
    }
    header("Location: ?action=edit&page=" . urlencode($pageName));
    exit;
}

// Delete page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete' && isLoggedIn()) {
    $pageName = $_POST['page'] ?? '';
    if ($pageName === 'home') {
        setFlash('error', 'Cannot delete the home page');
        header('Location: ?action=dashboard');
        exit;
    }
    if (deletePage($pageName)) {
        setFlash('success', 'Page "' . $pageName . '" deleted');
    } else {
        setFlash('error', 'Failed to delete page');
    }
    header('Location: ?action=dashboard');
    exit;
}

// Create new page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create' && isLoggedIn()) {
    $newName = preg_replace('/[^a-z0-9_-]/i', '', strtolower($_POST['page_name'] ?? ''));
    if (empty($newName)) {
        setFlash('error', 'Invalid page name');
        header('Location: ?action=dashboard');
        exit;
    }
    if (file_exists(CONTENT_DIR . '/' . $newName . '.json')) {
        setFlash('error', 'Page "' . $newName . '" already exists');
        header('Location: ?action=dashboard');
        exit;
    }

    $titleEn = $_POST['page_title_en'] ?: ucfirst($newName);
    $titleAr = $_POST['page_title'] ?: ucfirst($newName);

    $template = [
        'en' => [
            'site' => ['name' => $titleEn, 'nameShort' => $titleEn, 'arabicName' => $titleAr, 'tagline' => '', 'description' => '', 'logo' => '', 'favicon' => ''],
            'nav' => ['about' => 'About', 'divisions' => 'Divisions', 'services' => 'Services', 'team' => 'Our Team', 'contact' => 'Contact', 'system' => 'System'],
            'hero' => ['title' => '', 'titleLine2' => '', 'arabicName' => '', 'subtitle' => '', 'ctaText' => 'Learn More', 'ctaLink' => '#'],
            'stats' => [],
            'about' => ['title' => 'About Us', 'subtitle' => '', 'content' => '', 'content2' => '', 'features' => []],
            'divisions' => ['title' => 'Our Divisions', 'subtitle' => '', 'items' => []],
            'services' => ['title' => 'Our Services', 'subtitle' => '', 'items' => []],
            'team' => ['title' => 'Our Team', 'subtitle' => '', 'items' => []],
            'contact' => ['title' => 'Contact Us', 'subtitle' => '', 'address' => '', 'phone' => '', 'email' => '', 'website' => '', 'workingHours' => '', 'form' => []],
            'socialMedia' => ['title' => 'Follow Us', 'items' => []],
            'footer' => ['copyright' => '© ' . date('Y') . ' All rights reserved.', 'links' => []]
        ],
        'ar' => [
            'site' => ['name' => $titleAr, 'nameShort' => $titleAr, 'arabicName' => $titleAr, 'tagline' => '', 'description' => '', 'logo' => '', 'favicon' => ''],
            'nav' => ['about' => 'عن المجموعة', 'divisions' => 'الأقسام', 'services' => 'الخدمات', 'team' => 'فريقنا', 'contact' => 'تواصل معنا', 'system' => 'النظام'],
            'hero' => ['title' => '', 'titleLine2' => '', 'arabicName' => '', 'subtitle' => '', 'ctaText' => 'اقرأ المزيد', 'ctaLink' => '#'],
            'stats' => [],
            'about' => ['title' => 'عن المجموعة', 'subtitle' => '', 'content' => '', 'content2' => '', 'features' => []],
            'divisions' => ['title' => 'أقسامنا', 'subtitle' => '', 'items' => []],
            'services' => ['title' => 'خدماتنا', 'subtitle' => '', 'items' => []],
            'team' => ['title' => 'فريقنا', 'subtitle' => '', 'items' => []],
            'contact' => ['title' => 'تواصل معنا', 'subtitle' => '', 'address' => '', 'phone' => '', 'email' => '', 'website' => '', 'workingHours' => '', 'form' => []],
            'socialMedia' => ['title' => 'تابعنا', 'items' => []],
            'footer' => ['copyright' => '© ' . date('Y') . ' جميع الحقوق محفوظة.', 'links' => []]
        ],
        'theme' => [
            'primaryColor' => '#006B6B', 'secondaryColor' => '#004D4D', 'accentColor' => '#C8A951',
            'backgroundColor' => '#F5F7FA', 'surfaceColor' => '#FFFFFF',
            'textPrimary' => '#1A1A2E', 'textSecondary' => '#4A5568', 'textMuted' => '#A0AEC0',
            'successColor' => '#38B27A', 'infoColor' => '#3182CE', 'warningColor' => '#DD6B20', 'dangerColor' => '#E53E3E',
            'gradientStart' => '#006B6B', 'gradientEnd' => '#00897B', 'goldAccent' => '#C8A951'
        ]
    ];

    if (savePage($newName, $template)) {
        setFlash('success', 'Page "' . $newName . '" created');
        header("Location: ?action=edit&page=" . urlencode($newName));
        exit;
    } else {
        setFlash('error', 'Failed to create page');
        header('Location: ?action=dashboard');
        exit;
    }
}

// Image upload handler (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_image' && isLoggedIn()) {
    header('Content-Type: application/json');
    if (empty($_FILES['image'])) {
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }
    $file = $_FILES['image'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo json_encode(['error' => 'Invalid file type. Allowed: jpg, png, gif, webp']);
        exit;
    }
    if ($file['size'] > $maxSize) {
        echo json_encode(['error' => 'File too large. Max 5MB']);
        exit;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Upload error: ' . $file['error']]);
        exit;
    }

    $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
    $dest = UPLOAD_DIR . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $url = '../uploads/' . $filename;
        echo json_encode(['url' => $url, 'filename' => $filename]);
    } else {
        echo json_encode(['error' => 'Failed to save file']);
    }
    exit;
}

// Delete uploaded image handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_image' && isLoggedIn()) {
    $filename = $_POST['filename'] ?? '';
    $filepath = UPLOAD_DIR . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($filename));
    if ($filename && file_exists($filepath)) {
        unlink($filepath);
        setFlash('success', 'Image deleted');
    } else {
        setFlash('error', 'Image not found');
    }
    header('Location: ?action=images');
    exit;
}

// Check auth for protected actions
if (!isLoggedIn() && !in_array($action, ['login'])) {
    header('Location: ?action=login');
    exit;
}

// ─── Page Data ───────────────────────────────────────────────────
$pageData = null;
if ($action === 'edit' && $pageName) {
    $pageData = loadPage($pageName);
    if (!$pageData) {
        setFlash('error', 'Page not found');
        header('Location: ?action=dashboard');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ALMuhalab Admin — Content Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&family=Noto+Kufi+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #f5f7fa;
            --bg-secondary: #ffffff;
            --bg-surface: #f0f2f5;
            --bg-elevated: #e8edf2;
            --accent: #006B6B;
            --accent-hover: #005858;
            --accent-dim: rgba(0,107,107,0.1);
            --gold: #C8A951;
            --gold-dim: rgba(200,169,81,0.12);
            --teal-light: #00897B;
            --green: #38b27a;
            --green-dim: rgba(56,178,122,0.1);
            --blue: #3182CE;
            --blue-dim: rgba(49,130,206,0.1);
            --red: #E53E3E;
            --red-dim: rgba(229,62,62,0.1);
            --orange: #DD6B20;
            --orange-dim: rgba(221,107,32,0.1);
            --text: #1A1A2E;
            --text-secondary: #4A5568;
            --text-muted: #A0AEC0;
            --border: #E2E8F0;
            --border-focus: rgba(0,107,107,0.4);
            --shadow: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 12px 40px rgba(0,0,0,0.12);
            --radius: 12px;
            --radius-sm: 8px;
            --radius-xs: 6px;
            --transition: 0.2s ease;
            --font: 'Inter', -apple-system, BlinkMacSystemFont, 'Noto Kufi Arabic', sans-serif;
            --font-ar: 'Noto Kufi Arabic', 'Inter', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }
        [dir="rtl"] { font-family: var(--font-ar); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font); background: var(--bg-primary); color: var(--text); line-height: 1.6; min-height: 100vh; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        /* Login */
        .login-wrapper { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #004D4D 0%, #006B6B 40%, #00897B 100%); padding: 24px; }
        .login-card { background: var(--bg-secondary); border-radius: 20px; padding: 48px 40px; width: 100%; max-width: 420px; box-shadow: var(--shadow-lg); }
        .login-logo { text-align: center; margin-bottom: 32px; }
        .login-logo h1 { font-size: 26px; font-weight: 700; color: var(--accent); }
        .login-logo p { font-size: 13px; color: var(--text-muted); margin-top: 6px; }
        .login-divider { width: 60px; height: 3px; background: linear-gradient(90deg, var(--accent), var(--gold)); margin: 24px auto; border-radius: 2px; }

        /* Forms */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 5px; }
        .form-input { width: 100%; padding: 9px 12px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-family: var(--font); font-size: 13px; transition: all var(--transition); }
        .form-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); background: var(--bg-secondary); }
        .form-input::placeholder { color: var(--text-muted); }
        textarea.form-input { resize: vertical; min-height: 70px; line-height: 1.5; }
        select.form-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23A0AEC0'%3E%3Cpath d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; padding-right: 36px; cursor: pointer; }
        [dir="rtl"] select.form-input { background-position: left 12px center; padding-right: 12px; padding-left: 36px; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1px solid transparent; border-radius: var(--radius-sm); font-family: var(--font); font-size: 13px; font-weight: 600; cursor: pointer; transition: all var(--transition); text-decoration: none; white-space: nowrap; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,107,107,0.3); }
        .btn-gold { background: var(--gold); color: #1A1A2E; }
        .btn-gold:hover { background: #b89941; }
        .btn-secondary { background: var(--bg-surface); color: var(--text-secondary); border-color: var(--border); }
        .btn-secondary:hover { background: var(--bg-elevated); color: var(--text); }
        .btn-danger { background: var(--red-dim); color: var(--red); border-color: rgba(229,62,62,0.2); }
        .btn-danger:hover { background: rgba(229,62,62,0.2); }
        .btn-ghost { background: transparent; color: var(--text-secondary); }
        .btn-ghost:hover { background: var(--bg-surface); color: var(--text); }
        .btn-sm { padding: 5px 12px; font-size: 12px; }
        .btn-full { width: 100%; justify-content: center; padding: 12px 20px; }

        /* Layout */
        .app { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--bg-secondary); border-inline-end: 1px solid var(--border); display: flex; flex-direction: column; position: fixed; top: 0; inset-inline-start: 0; bottom: 0; z-index: 100; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid var(--border); background: linear-gradient(135deg, var(--accent), var(--teal-light)); color: #fff; }
        .sidebar-header h2 { font-size: 18px; font-weight: 700; }
        .sidebar-header p { font-size: 11px; opacity: 0.8; margin-top: 2px; }
        .sidebar-nav { flex: 1; padding: 12px 8px; overflow-y: auto; }
        .nav-section-title { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.5px; padding: 12px 12px 6px; }
        .nav-link { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: var(--radius-xs); color: var(--text-secondary); text-decoration: none; font-size: 13px; font-weight: 500; transition: all var(--transition); margin-bottom: 1px; }
        .nav-link:hover { background: var(--bg-surface); color: var(--text); }
        .nav-link.active { background: var(--accent-dim); color: var(--accent); font-weight: 600; }
        .nav-link .icon { font-size: 16px; width: 20px; text-align: center; }
        .sidebar-footer { padding: 12px 8px; border-top: 1px solid var(--border); }
        .main-content { flex: 1; margin-inline-start: 260px; min-height: 100vh; }
        .topbar { display: flex; align-items: center; justify-content: space-between; padding: 14px 28px; border-bottom: 1px solid var(--border); background: var(--bg-secondary); position: sticky; top: 0; z-index: 50; }
        .topbar-title { font-size: 18px; font-weight: 600; }
        .topbar-actions { display: flex; align-items: center; gap: 10px; }
        .content-area { padding: 28px; }

        /* Language Toggle */
        .lang-toggle { display: inline-flex; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; }
        .lang-btn { padding: 6px 14px; border: none; background: transparent; font-family: var(--font); font-size: 12px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: all var(--transition); }
        .lang-btn.active { background: var(--accent); color: #fff; }
        .lang-btn:hover:not(.active) { background: var(--bg-elevated); color: var(--text); }

        /* Flash */
        .flash { padding: 12px 18px; border-radius: var(--radius-sm); margin-bottom: 20px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        .flash-success { background: var(--green-dim); color: var(--green); border: 1px solid rgba(56,178,122,0.2); }
        .flash-error { background: var(--red-dim); color: var(--red); border: 1px solid rgba(229,62,62,0.2); }

        /* Dashboard */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); padding: 18px; transition: all var(--transition); }
        .stat-card:hover { border-color: var(--accent); box-shadow: var(--shadow-md); }
        .stat-card .stat-icon { font-size: 22px; margin-bottom: 8px; }
        .stat-card .stat-value { font-size: 24px; font-weight: 700; color: var(--accent); }
        .stat-card .stat-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }
        .stat-card.gold .stat-value { color: var(--gold); }
        .stat-card.green .stat-value { color: var(--green); }
        .stat-card.blue .stat-value { color: var(--blue); }

        /* Table */
        .table-container { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .table-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); }
        .table-header h3 { font-size: 15px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: start; padding: 10px 20px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); background: var(--bg-surface); }
        td { padding: 14px 20px; font-size: 13px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(0,107,107,0.02); }
        .page-name { font-weight: 600; color: var(--accent); }
        .page-title { color: var(--text-secondary); }
        .page-date { color: var(--text-muted); font-size: 12px; }
        .actions-cell { display: flex; gap: 6px; }

        /* Editor */
        .editor-layout { display: grid; grid-template-columns: 1fr 280px; gap: 24px; }
        .editor-main { min-width: 0; }
        .editor-sidebar { position: sticky; top: 80px; max-height: calc(100vh - 120px); overflow-y: auto; }
        .section-panel { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 14px; overflow: hidden; }
        .section-header { display: flex; align-items: center; justify-content: space-between; padding: 13px 16px; background: var(--bg-surface); cursor: pointer; user-select: none; transition: background var(--transition); }
        .section-header:hover { background: var(--bg-elevated); }
        .section-header h3 { font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .section-header .toggle { color: var(--text-muted); transition: transform 0.3s ease; font-size: 12px; }
        .section-header.collapsed .toggle { transform: rotate(-90deg); }
        .section-body { padding: 16px; }
        .section-body.hidden { display: none; }
        .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .field-full { grid-column: 1 / -1; }
        .field-hint { font-size: 11px; color: var(--text-muted); margin-top: 3px; font-style: italic; }

        /* Bilingual Label */
        .bilingual-label { font-size: 10px; font-weight: 700; color: var(--accent); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; display: flex; align-items: center; gap: 4px; }

        /* List Items */
        .list-items { display: flex; flex-direction: column; gap: 10px; }
        .list-item { background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; }
        .list-item-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .list-item-header span { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .list-item-remove { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 14px; padding: 2px 6px; border-radius: 4px; transition: all var(--transition); }
        .list-item-remove:hover { background: var(--red-dim); color: var(--red); }
        .add-item-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 10px; background: transparent; border: 2px dashed var(--border); border-radius: var(--radius-sm); color: var(--text-muted); font-family: var(--font); font-size: 12px; font-weight: 500; cursor: pointer; transition: all var(--transition); }
        .add-item-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-dim); }

        /* Color */
        .color-field { display: flex; align-items: center; gap: 8px; }
        .color-swatch { width: 32px; height: 32px; border-radius: var(--radius-xs); border: 2px solid var(--border); cursor: pointer; flex-shrink: 0; }
        .color-swatch::-webkit-color-swatch-wrapper { padding: 0; }
        .color-swatch::-webkit-color-swatch { border: none; border-radius: 4px; }
        .color-field .form-input { flex: 1; font-family: var(--font-mono); font-size: 12px; }
        .theme-preview { display: flex; gap: 6px; margin-top: 12px; padding: 10px; background: var(--bg-surface); border-radius: var(--radius-sm); border: 1px solid var(--border); }
        .theme-preview-swatch { width: 28px; height: 28px; border-radius: 6px; border: 2px solid var(--border); }
        .preset-colors { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .preset-color { width: 28px; height: 28px; border-radius: 6px; border: 2px solid transparent; cursor: pointer; transition: all var(--transition); }
        .preset-color:hover { transform: scale(1.15); }

        /* Tabs */
        .tab-nav { display: flex; gap: 4px; padding: 4px; background: var(--bg-surface); border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid var(--border); }
        .tab-btn { flex: 1; padding: 9px 16px; background: transparent; border: none; border-radius: var(--radius-xs); color: var(--text-muted); font-family: var(--font); font-size: 13px; font-weight: 500; cursor: pointer; transition: all var(--transition); }
        .tab-btn.active { background: var(--bg-secondary); color: var(--accent); font-weight: 600; box-shadow: var(--shadow); }
        .tab-btn:hover:not(.active) { color: var(--text); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* JSON Editor */
        .json-editor-wrap { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .json-editor-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--bg-surface); border-bottom: 1px solid var(--border); }
        .json-editor-header h3 { font-size: 13px; font-weight: 600; }
        .json-textarea { width: 100%; min-height: 500px; padding: 16px; background: #1a2332; color: #7FDBCA; font-family: var(--font-mono); font-size: 13px; line-height: 1.7; border: none; resize: vertical; }
        .json-textarea:focus { outline: none; }

        /* Quick Actions */
        .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .quick-action { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 14px 8px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-sm); text-decoration: none; color: var(--text-secondary); font-size: 11px; font-weight: 500; transition: all var(--transition); cursor: pointer; }
        .quick-action:hover { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
        .quick-action .qa-icon { font-size: 20px; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 24px; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
        .modal-overlay.active { opacity: 1; pointer-events: all; }
        .modal { background: var(--bg-secondary); border-radius: var(--radius); width: 100%; max-width: 480px; box-shadow: var(--shadow-lg); transform: scale(0.95); transition: transform 0.3s ease; }
        .modal-overlay.active .modal { transform: scale(1); }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-bottom: 1px solid var(--border); }
        .modal-header h3 { font-size: 16px; font-weight: 600; }
        .modal-close { background: none; border: none; color: var(--text-muted); font-size: 20px; cursor: pointer; padding: 4px; }
        .modal-close:hover { color: var(--text); }
        .modal-body { padding: 24px; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 24px; border-top: 1px solid var(--border); }

        /* Empty */
        .empty-state { text-align: center; padding: 48px 24px; }
        .empty-state .icon { font-size: 40px; margin-bottom: 12px; opacity: 0.5; }
        .empty-state h3 { font-size: 16px; font-weight: 600; margin-bottom: 6px; }
        .empty-state p { color: var(--text-muted); font-size: 13px; margin-bottom: 20px; }

        @media (max-width: 1024px) { .editor-layout { grid-template-columns: 1fr; } .editor-sidebar { position: static; max-height: none; } }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-inline-start: 0; } .content-area { padding: 16px; } .field-row { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>

<?php if ($action === 'login'): ?>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <h1>🏢 ALMuhalab</h1>
            <p>Content Management System</p>
        </div>
        <div class="login-divider"></div>
        <?php if ($msg = getFlash('error')): ?>
            <div class="flash flash-error">⚠ <?= sanitize($msg) ?></div>
        <?php endif; ?>
        <form method="POST" action="?action=login">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label><?= $lang === 'ar' ? 'كلمة مرور المدير' : 'Admin Password' ?></label>
                <input type="password" name="password" class="form-input" placeholder="Enter password" autofocus required>
            </div>
            <button type="submit" class="btn btn-primary btn-full"><?= $lang === 'ar' ? 'تسجيل الدخول ←' : 'Sign In →' ?></button>
        </form>
        <div style="text-align:center; margin-top:20px;">
            <a href="../" style="color:var(--text-muted); font-size:12px; text-decoration:none;">← <?= $lang === 'ar' ? 'العودة للموقع' : 'Back to Site' ?></a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="app">
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>🏢 ALMuhalab</h2>
            <p>Content Manager</p>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title"><?= $lang === 'ar' ? 'الرئيسية' : 'Main' ?></div>
            <a href="?action=dashboard" class="nav-link <?= $action === 'dashboard' ? 'active' : '' ?>">
                <span class="icon">📊</span> <?= $lang === 'ar' ? 'لوحة التحكم' : 'Dashboard' ?>
            </a>
            <a href="javascript:void(0)" onclick="openCreateModal()" class="nav-link">
                <span class="icon">➕</span> <?= $lang === 'ar' ? 'صفحة جديدة' : 'New Page' ?>
            </a>
            <a href="?action=images" class="nav-link <?= $action === 'images' ? 'active' : '' ?>">
                <span class="icon">📷</span> <?= $lang === 'ar' ? 'الصور' : 'Images' ?>
            </a>
            <div class="nav-section-title"><?= $lang === 'ar' ? 'الصفحات' : 'Pages' ?></div>
            <?php foreach (getPageFiles() as $pg): ?>
                <a href="?action=edit&page=<?= urlencode($pg['name']) ?>" class="nav-link <?= ($action === 'edit' && $pageName === $pg['name']) ? 'active' : '' ?>">
                    <span class="icon">📄</span> <?= sanitize($pg['title']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <div style="padding: 8px 12px; margin-bottom: 8px;">
                <form method="POST" action="?action=setlang">
                    <div class="lang-toggle">
                        <button type="submit" name="lang" value="ar" class="lang-btn <?= $lang === 'ar' ? 'active' : '' ?>">عربي</button>
                        <button type="submit" name="lang" value="en" class="lang-btn <?= $lang === 'en' ? 'active' : '' ?>">EN</button>
                    </div>
                </form>
            </div>
            <a href="?action=logout" class="nav-link"><span class="icon">🚪</span> <?= $lang === 'ar' ? 'خروج' : 'Sign Out' ?></a>
            <a href="../" target="_blank" class="nav-link"><span class="icon">🌐</span> <?= $lang === 'ar' ? 'عرض الموقع' : 'View Site' ?></a>
        </div>
    </aside>

    <div class="main-content">

<?php if ($action === 'dashboard'): ?>
        <div class="topbar">
            <h1 class="topbar-title"><?= $lang === 'ar' ? 'لوحة التحكم' : 'Dashboard' ?></h1>
            <div class="topbar-actions">
                <a href="../" target="_blank" class="btn btn-secondary btn-sm">🌐 <?= $lang === 'ar' ? 'عرض' : 'View' ?></a>
                <button onclick="openCreateModal()" class="btn btn-primary btn-sm">➕ <?= $lang === 'ar' ? 'جديد' : 'New' ?></button>
            </div>
        </div>
        <div class="content-area">
            <?php if ($msg = getFlash('success')): ?><div class="flash flash-success">✅ <?= sanitize($msg) ?></div><?php endif; ?>
            <?php if ($msg = getFlash('error')): ?><div class="flash flash-error">⚠ <?= sanitize($msg) ?></div><?php endif; ?>

            <?php
            $pages = getPageFiles();
            $home = loadPage('home');
            $divCount = count($home['en']['divisions']['items'] ?? $home['ar']['divisions']['items'] ?? []);
            $svcCount = count($home['en']['services']['items'] ?? $home['ar']['services']['items'] ?? []);
            $teamCount = count($home['en']['team']['items'] ?? $home['ar']['team']['items'] ?? []);
            $socialCount = count($home['en']['socialMedia']['items'] ?? $home['ar']['socialMedia']['items'] ?? []);
            ?>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">📄</div><div class="stat-value"><?= count($pages) ?></div><div class="stat-label"><?= $lang === 'ar' ? 'الصفحات' : 'Pages' ?></div></div>
                <div class="stat-card gold"><div class="stat-icon">🏢</div><div class="stat-value"><?= $divCount ?></div><div class="stat-label"><?= $lang === 'ar' ? 'الفروع' : 'Divisions' ?></div></div>
                <div class="stat-card green"><div class="stat-icon">🔧</div><div class="stat-value"><?= $svcCount ?></div><div class="stat-label"><?= $lang === 'ar' ? 'الخدمات' : 'Services' ?></div></div>
                <div class="stat-card blue"><div class="stat-icon">👥</div><div class="stat-value"><?= $teamCount ?></div><div class="stat-label"><?= $lang === 'ar' ? 'الفريق' : 'Team' ?></div></div>
            </div>

            <?php if ($home): ?>
            <div class="section-panel" style="margin-bottom: 20px;">
                <div class="section-header">
                    <h3>🏢 <?= $lang === 'ar' ? 'معلومات الشركة' : 'Company Info' ?></h3>
                    <a href="?action=edit&page=home" class="btn btn-secondary btn-sm">✏️ <?= $lang === 'ar' ? 'تعديل' : 'Edit' ?></a>
                </div>
                <div class="section-body">
                    <div class="field-row">
                        <div><p style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">English</p><p style="font-weight:600;font-size:14px;"><?= sanitize($home['en']['site']['name'] ?? '-') ?></p></div>
                        <div><p style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">العربية</p><p style="font-weight:600;font-size:14px;direction:rtl;"><?= sanitize($home['ar']['site']['name'] ?? '-') ?></p></div>
                    </div>
                    <div class="field-row" style="margin-top:12px;">
                        <div><p style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">Email</p><p style="font-weight:600;"><?= sanitize($home['en']['contact']['email'] ?? '-') ?></p></div>
                        <div><p style="font-size:11px;color:var(--text-muted);margin-bottom:3px;">Phone</p><p style="font-weight:600;"><?= sanitize($home['en']['contact']['phone'] ?? '-') ?></p></div>
                    </div>
                    <?php if (!empty($home['theme'])): ?>
                    <div class="theme-preview" style="margin-top:14px;">
                        <div class="theme-preview-swatch" style="background:<?= $home['theme']['primaryColor'] ?? '#006B6B' ?>;"></div>
                        <div class="theme-preview-swatch" style="background:<?= $home['theme']['secondaryColor'] ?? '#004D4D' ?>;"></div>
                        <div class="theme-preview-swatch" style="background:<?= $home['theme']['accentColor'] ?? '#C8A951' ?>;"></div>
                        <div class="theme-preview-swatch" style="background:<?= $home['theme']['backgroundColor'] ?? '#F5F7FA' ?>;"></div>
                        <div class="theme-preview-swatch" style="background:<?= $home['theme']['goldAccent'] ?? '#C8A951' ?>;"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($home['en']['socialMedia']['items'])): ?>
            <div class="section-panel" style="margin-bottom: 20px;">
                <div class="section-header"><h3>📱 <?= $lang === 'ar' ? 'التواصل الاجتماعي' : 'Social Media' ?></h3></div>
                <div class="section-body">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php foreach ($home['en']['socialMedia']['items'] as $s): ?>
                            <a href="<?= sanitize($s['url'] ?? '#') ?>" target="_blank" style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:var(--bg-surface);border:1px solid var(--border);border-radius:20px;text-decoration:none;color:var(--text-secondary);font-size:12px;"><?= $s['icon'] ?? '🔗' ?> <?= sanitize($s['label'] ?? '') ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="table-container">
                <div class="table-header"><h3><?= $lang === 'ar' ? 'جميع الصفحات' : 'All Pages' ?></h3></div>
                <?php if (empty($pages)): ?>
                    <div class="empty-state"><div class="icon">📂</div><h3><?= $lang === 'ar' ? 'لا توجد صفحات' : 'No Pages Yet' ?></h3><p><?= $lang === 'ar' ? 'أنشئ أول صفحة للبدء' : 'Create your first page to get started.' ?></p><button onclick="openCreateModal()" class="btn btn-primary">➕ <?= $lang === 'ar' ? 'إنشاء' : 'Create' ?></button></div>
                <?php else: ?>
                    <table>
                        <thead><tr><th><?= $lang === 'ar' ? 'الاسم' : 'Name' ?></th><th><?= $lang === 'ar' ? 'العنوان' : 'Title' ?></th><th><?= $lang === 'ar' ? 'آخر تعديل' : 'Modified' ?></th><th><?= $lang === 'ar' ? 'إجراءات' : 'Actions' ?></th></tr></thead>
                        <tbody>
                            <?php foreach ($pages as $pg): ?>
                                <tr>
                                    <td><span class="page-name"><?= sanitize($pg['name']) ?>.json</span></td>
                                    <td><span class="page-title"><?= sanitize($pg['title']) ?></span></td>
                                    <td><span class="page-date"><?= date('M d, Y H:i', $pg['modified']) ?></span></td>
                                    <td><div class="actions-cell">
                                        <a href="?action=edit&page=<?= urlencode($pg['name']) ?>" class="btn btn-secondary btn-sm">✏️</a>
                                        <a href="../<?= $pg['name'] === 'home' ? '' : urlencode($pg['name']) ?>/" target="_blank" class="btn btn-ghost btn-sm">🌐</a>
                                        <?php if ($pg['name'] !== 'home'): ?>
                                            <form method="POST" action="?action=delete" onsubmit="return confirm('Delete?')"><input type="hidden" name="page" value="<?= sanitize($pg['name']) ?>"><button type="submit" class="btn btn-danger btn-sm">🗑</button></form>
                                        <?php endif; ?>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

<?php elseif ($action === 'edit' && $pageData): ?>
        <div class="topbar">
            <h1 class="topbar-title"><?= $lang === 'ar' ? 'تعديل' : 'Edit' ?>: <span style="color:var(--accent)"><?= sanitize($pageName) ?></span></h1>
            <div class="topbar-actions">
                <a href="../" target="_blank" class="btn btn-ghost btn-sm">🌐 <?= $lang === 'ar' ? 'معاينة' : 'Preview' ?></a>
                <a href="?action=dashboard" class="btn btn-secondary btn-sm">← <?= $lang === 'ar' ? 'رجوع' : 'Back' ?></a>
            </div>
        </div>
        <div class="content-area">
            <?php if ($msg = getFlash('success')): ?><div class="flash flash-success">✅ <?= sanitize($msg) ?></div><?php endif; ?>
            <?php if ($msg = getFlash('error')): ?><div class="flash flash-error">⚠ <?= sanitize($msg) ?></div><?php endif; ?>

            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchTab('visual')">🎨 <?= $lang === 'ar' ? 'المحرر المرئي' : 'Visual Editor' ?></button>
                <button class="tab-btn" onclick="switchTab('json')">{ } JSON</button>
            </div>

            <div id="tab-visual" class="tab-content active">
                <form method="POST" action="?action=save" id="editorForm">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="page" value="<?= sanitize($pageName) ?>">
                    <input type="hidden" name="content" id="jsonContent">

                    <div class="editor-layout">
                        <div class="editor-main">

                            <?php
                            // Helper: get value from bilingual structure
                            function gv($data, $lang, $path, $default = '') {
                                $v = $data[$lang] ?? [];
                                $parts = explode('.', $path);
                                foreach ($parts as $p) { $v = $v[$p] ?? null; }
                                return $v !== null ? $v : $default;
                            }
                            ?>

                            <!-- ═══ SITE SETTINGS ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>🌐 <?= $lang === 'ar' ? 'إعدادات الموقع' : 'Site Settings' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <div class="bilingual-label">🌐 English</div>
                                    <div class="field-row">
                                        <div class="form-group"><label>Site Name</label><input type="text" class="form-input" data-field="en.site.name" value="<?= sanitize(gv($pageData,'en','site.name')) ?>"></div>
                                        <div class="form-group"><label>Name Short</label><input type="text" class="form-input" data-field="en.site.nameShort" value="<?= sanitize(gv($pageData,'en','site.nameShort')) ?>"></div>
                                    </div>
                                    <div class="field-row">
                                        <div class="form-group"><label>Tagline</label><input type="text" class="form-input" data-field="en.site.tagline" value="<?= sanitize(gv($pageData,'en','site.tagline')) ?>"></div>
                                        <div class="form-group"><label>Arabic Name</label><input type="text" class="form-input" data-field="en.site.arabicName" value="<?= sanitize(gv($pageData,'en','site.arabicName')) ?>" dir="rtl"></div>
                                    </div>
                                    <div class="form-group"><label>Description</label><textarea class="form-input" data-field="en.site.description" rows="2"><?= sanitize(gv($pageData,'en','site.description')) ?></textarea></div>

                                    <div class="bilingual-label" style="margin-top:16px;">🌐 العربية</div>
                                    <div class="field-row">
                                        <div class="form-group"><label>اسم الموقع</label><input type="text" class="form-input" data-field="ar.site.name" value="<?= sanitize(gv($pageData,'ar','site.name')) ?>" dir="rtl"></div>
                                        <div class="form-group"><label>الاسم المختصر</label><input type="text" class="form-input" data-field="ar.site.nameShort" value="<?= sanitize(gv($pageData,'ar','site.nameShort')) ?>" dir="rtl"></div>
                                    </div>
                                    <div class="field-row">
                                        <div class="form-group"><label>الشعار</label><input type="text" class="form-input" data-field="ar.site.tagline" value="<?= sanitize(gv($pageData,'ar','site.tagline')) ?>" dir="rtl"></div>
                                        <div class="form-group"><label>الاسم بالإنجليزية</label><input type="text" class="form-input" data-field="ar.site.arabicName" value="<?= sanitize(gv($pageData,'ar','site.arabicName')) ?>"></div>
                                    </div>
                                    <div class="form-group"><label>الوصف</label><textarea class="form-input" data-field="ar.site.description" rows="2" dir="rtl"><?= sanitize(gv($pageData,'ar','site.description')) ?></textarea></div>

                                    <div class="field-row" style="margin-top:12px;">
                                        <div class="form-group"><label>Logo URL</label><input type="text" class="form-input" data-field="en.site.logo" value="<?= sanitize(gv($pageData,'en','site.logo')) ?>" placeholder="assets/images/logo.png"></div>
                                        <div class="form-group"><label>Favicon URL</label><input type="text" class="form-input" data-field="en.site.favicon" value="<?= sanitize(gv($pageData,'en','site.favicon')) ?>"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══ HERO SECTION ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>🎯 <?= $lang === 'ar' ? 'قسم البطل' : 'Hero Section' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <div class="bilingual-label">🌐 English</div>
                                    <div class="field-row">
                                        <div class="form-group"><label>Title</label><input type="text" class="form-input" data-field="en.hero.title" value="<?= sanitize(gv($pageData,'en','hero.title')) ?>"></div>
                                        <div class="form-group"><label>Title Line 2</label><input type="text" class="form-input" data-field="en.hero.titleLine2" value="<?= sanitize(gv($pageData,'en','hero.titleLine2')) ?>"></div>
                                    </div>
                                    <div class="form-group"><label>Subtitle</label><input type="text" class="form-input" data-field="en.hero.subtitle" value="<?= sanitize(gv($pageData,'en','hero.subtitle')) ?>"></div>
                                    <div class="field-row">
                                        <div class="form-group"><label>CTA Text</label><input type="text" class="form-input" data-field="en.hero.ctaText" value="<?= sanitize(gv($pageData,'en','hero.ctaText')) ?>"></div>
                                        <div class="form-group"><label>CTA Link</label><input type="text" class="form-input" data-field="en.hero.ctaLink" value="<?= sanitize(gv($pageData,'en','hero.ctaLink')) ?>"></div>
                                    </div>

                                    <div class="bilingual-label" style="margin-top:16px;">🌐 العربية</div>
                                    <div class="field-row">
                                        <div class="form-group"><label>العنوان</label><input type="text" class="form-input" data-field="ar.hero.title" value="<?= sanitize(gv($pageData,'ar','hero.title')) ?>" dir="rtl"></div>
                                        <div class="form-group"><label>العنوان (سطر 2)</label><input type="text" class="form-input" data-field="ar.hero.titleLine2" value="<?= sanitize(gv($pageData,'ar','hero.titleLine2')) ?>" dir="rtl"></div>
                                    </div>
                                    <div class="form-group"><label>العنوان الفرعي</label><input type="text" class="form-input" data-field="ar.hero.subtitle" value="<?= sanitize(gv($pageData,'ar','hero.subtitle')) ?>" dir="rtl"></div>
                                    <div class="field-row">
                                        <div class="form-group"><label>نص الزر</label><input type="text" class="form-input" data-field="ar.hero.ctaText" value="<?= sanitize(gv($pageData,'ar','hero.ctaText')) ?>" dir="rtl"></div>
                                        <div class="form-group"><label>رابط الزر</label><input type="text" class="form-input" data-field="ar.hero.ctaLink" value="<?= sanitize(gv($pageData,'ar','hero.ctaLink')) ?>"></div>
                                    </div>
                                    <div class="field-row" style="margin-top:12px;">
                                        <div class="form-group">
                                            <label>🖼 <?= $lang === 'ar' ? 'صورة الخلفية' : 'Background Image' ?></label>
                                            <div style="display:flex;align-items:center;gap:6px;">
                                                <input type="text" class="form-input" data-field="en.hero.backgroundImage" value="<?= sanitize(gv($pageData,'en','hero.backgroundImage')) ?>" placeholder="https://...">
                                                <label class="btn btn-secondary btn-sm" style="cursor:pointer;padding:6px 8px;flex-shrink:0;" title="Upload">📷<input type="file" accept="image/*" style="display:none;" onchange="uploadImage(this, 'en.hero.backgroundImage')"></label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>🖼 <?= $lang === 'ar' ? 'صورة الخلفية' : 'Background Image' ?> (AR)</label>
                                            <div style="display:flex;align-items:center;gap:6px;">
                                                <input type="text" class="form-input" data-field="ar.hero.backgroundImage" value="<?= sanitize(gv($pageData,'ar','hero.backgroundImage')) ?>" placeholder="https://...">
                                                <label class="btn btn-secondary btn-sm" style="cursor:pointer;padding:6px 8px;flex-shrink:0;" title="Upload">📷<input type="file" accept="image/*" style="display:none;" onchange="uploadImage(this, 'ar.hero.backgroundImage')"></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══ STATS ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>📊 <?= $lang === 'ar' ? 'الإحصائيات' : 'Statistics' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <?php foreach (['en', 'ar'] as $l): ?>
                                    <div class="bilingual-label">🌐 <?= $l === 'en' ? 'English' : 'العربية' ?></div>
                                    <div class="list-items" id="statsList_<?= $l ?>">
                                        <?php foreach ((gv($pageData, $l, 'stats', []) as $i => $stat): ?>
                                            <div class="list-item" data-index="<?= $i ?>">
                                                <div class="list-item-header"><span>Stat #<?= $i + 1 ?></span><button type="button" class="list-item-remove" onclick="removeItem(this)">✕</button></div>
                                                <div class="field-row">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.stats" data-index="<?= $i ?>" data-key="icon" value="<?= sanitize($stat['icon'] ?? '') ?>" placeholder="Icon">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.stats" data-index="<?= $i ?>" data-key="value" value="<?= sanitize($stat['value'] ?? '') ?>" placeholder="Value">
                                                </div>
                                                <input type="text" class="form-input" style="margin-top:8px;" data-field-array="<?= $l ?>.stats" data-index="<?= $i ?>" data-key="label" value="<?= sanitize($stat['label'] ?? '') ?>" placeholder="Label" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="add-item-btn" onclick="addStat('<?= $l ?>')">+ <?= $l === 'en' ? 'Add Stat' : 'إضافة إحصائية' ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ═══ DIVISIONS ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>🏢 <?= $lang === 'ar' ? 'الفروع' : 'Divisions' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <?php foreach (['en', 'ar'] as $l): ?>
                                    <div class="bilingual-label">🌐 <?= $l === 'en' ? 'English' : 'العربية' ?></div>
                                    <div class="list-items" id="divisionsList_<?= $l ?>">
                                        <?php foreach ((gv($pageData, $l, 'divisions.items', []) as $i => $div): ?>
                                            <div class="list-item" data-index="<?= $i ?>">
                                                <div class="list-item-header"><span>#<?= $i + 1 ?></span><button type="button" class="list-item-remove" onclick="removeItem(this)">✕</button></div>
                                                <div class="field-row">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.divisions.items" data-index="<?= $i ?>" data-key="icon" value="<?= sanitize($div['icon'] ?? '') ?>" placeholder="Icon">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.divisions.items" data-index="<?= $i ?>" data-key="title" value="<?= sanitize($div['title'] ?? '') ?>" placeholder="Title" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>>
                                                </div>
                                                <div class="field-row" style="margin-top:8px;">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.divisions.items" data-index="<?= $i ?>" data-key="arabic" value="<?= sanitize($div['arabic'] ?? '') ?>" placeholder="Arabic Name" dir="rtl">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.divisions.items" data-index="<?= $i ?>" data-key="link" value="<?= sanitize($div['link'] ?? '') ?>" placeholder="Link URL">
                                                </div>
                                                <textarea class="form-input" style="margin-top:8px;" rows="2" data-field-array="<?= $l ?>.divisions.items" data-index="<?= $i ?>" data-key="description" placeholder="Description" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>><?= sanitize($div['description'] ?? '') ?></textarea>
                                                <div class="field-row" style="margin-top:8px;">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.divisions.items" data-index="<?= $i ?>" data-key="btnText" value="<?= sanitize($div['btnText'] ?? '') ?>" placeholder="Button Text" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>>
                                                    <select class="form-input" data-field-array="<?= $l ?>.divisions.items" data-index="<?= $i ?>" data-key="status">
                                                        <option value="active" <?= ($div['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                                        <option value="coming_soon" <?= ($div['status'] ?? '') === 'coming_soon' ? 'selected' : '' ?>>Coming Soon</option>
                                                        <option value="inactive" <?= ($div['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                    </select>
                                                </div>
                                                <div style="margin-top:8px;">
                                                    <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:3px;">🖼 Image</label>
                                                    <div style="display:flex;align-items:center;gap:6px;">
                                                        <input type="text" class="form-input" data-field-array="<?= $l ?>.divisions.items" data-index="<?= $i ?>" data-key="image" value="<?= sanitize($div['image'] ?? '') ?>" placeholder="Image URL">
                                                        <label class="btn btn-secondary btn-sm" style="cursor:pointer;padding:6px 8px;flex-shrink:0;" title="Upload Image">📷<input type="file" accept="image/*" style="display:none;" onchange="uploadImage(this, '')"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="add-item-btn" onclick="addDivision('<?= $l ?>')">+ <?= $l === 'en' ? 'Add Division' : 'إضافة فرع' ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ═══ SERVICES ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>🔧 <?= $lang === 'ar' ? 'الخدمات' : 'Services' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <?php foreach (['en', 'ar'] as $l): ?>
                                    <div class="bilingual-label">🌐 <?= $l === 'en' ? 'English' : 'العربية' ?></div>
                                    <div class="list-items" id="servicesList_<?= $l ?>">
                                        <?php foreach ((gv($pageData, $l, 'services.items', []) as $i => $svc): ?>
                                            <div class="list-item" data-index="<?= $i ?>">
                                                <div class="list-item-header"><span>#<?= $i + 1 ?></span><button type="button" class="list-item-remove" onclick="removeItem(this)">✕</button></div>
                                                <div class="field-row">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.services.items" data-index="<?= $i ?>" data-key="icon" value="<?= sanitize($svc['icon'] ?? '') ?>" placeholder="Icon">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.services.items" data-index="<?= $i ?>" data-key="title" value="<?= sanitize($svc['title'] ?? '') ?>" placeholder="Title" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>>
                                                </div>
                                                <textarea class="form-input" style="margin-top:8px;" rows="2" data-field-array="<?= $l ?>.services.items" data-index="<?= $i ?>" data-key="description" placeholder="Description" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>><?= sanitize($svc['description'] ?? '') ?></textarea>
                                                <input type="text" class="form-input" style="margin-top:8px;" data-field-array="<?= $l ?>.services.items" data-index="<?= $i ?>" data-key="link" value="<?= sanitize($svc['link'] ?? '') ?>" placeholder="Link URL">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="add-item-btn" onclick="addService('<?= $l ?>')">+ <?= $l === 'en' ? 'Add Service' : 'إضافة خدمة' ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ═══ TEAM ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>👥 <?= $lang === 'ar' ? 'الفريق' : 'Team Members' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <?php foreach (['en', 'ar'] as $l): ?>
                                    <div class="bilingual-label">🌐 <?= $l === 'en' ? 'English' : 'العربية' ?></div>
                                    <div class="list-items" id="teamList_<?= $l ?>">
                                        <?php foreach ((gv($pageData, $l, 'team.items', []) as $i => $m): ?>
                                            <div class="list-item" data-index="<?= $i ?>">
                                                <div class="list-item-header"><span>#<?= $i + 1 ?></span><button type="button" class="list-item-remove" onclick="removeItem(this)">✕</button></div>
                                                <div class="field-row">
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.team.items" data-index="<?= $i ?>" data-key="name" value="<?= sanitize($m['name'] ?? '') ?>" placeholder="Name" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>>
                                                    <input type="text" class="form-input" data-field-array="<?= $l ?>.team.items" data-index="<?= $i ?>" data-key="position" value="<?= sanitize($m['position'] ?? '') ?>" placeholder="Position" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>>
                                                </div>
                                                <textarea class="form-input" style="margin-top:8px;" rows="2" data-field-array="<?= $l ?>.team.items" data-index="<?= $i ?>" data-key="bio" placeholder="Bio" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>><?= sanitize($m['bio'] ?? '') ?></textarea>
                                                <div class="field-row" style="margin-top:8px;">
                                                    <div style="display:flex;align-items:center;gap:6px;">
                                                        <input type="text" class="form-input" data-field-array="<?= $l ?>.team.items" data-index="<?= $i ?>" data-key="image" value="<?= sanitize($m['image'] ?? '') ?>" placeholder="Image URL">
                                                        <label class="btn btn-secondary btn-sm" style="cursor:pointer;padding:6px 8px;flex-shrink:0;" title="Upload Photo">📷<input type="file" accept="image/*" style="display:none;" onchange="uploadImage(this, '')"></label>
                                                    </div>
                                                    <input type="email" class="form-input" data-field-array="<?= $l ?>.team.items" data-index="<?= $i ?>" data-key="email" value="<?= sanitize($m['email'] ?? '') ?>" placeholder="Email">
                                                </div>
                                                <input type="text" class="form-input" style="margin-top:8px;" data-field-array="<?= $l ?>.team.items" data-index="<?= $i ?>" data-key="linkedin" value="<?= sanitize($m['linkedin'] ?? '') ?>" placeholder="LinkedIn URL">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="add-item-btn" onclick="addTeamMember('<?= $l ?>')">+ <?= $l === 'en' ? 'Add Member' : 'إضافة عضو' ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ═══ CONTACT ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>📬 <?= $lang === 'ar' ? 'التواصل' : 'Contact' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <?php foreach (['en', 'ar'] as $l): ?>
                                    <div class="bilingual-label">🌐 <?= $l === 'en' ? 'English' : 'العربية' ?></div>
                                    <div class="field-row">
                                        <div class="form-group"><label>Title</label><input type="text" class="form-input" data-field="<?= $l ?>.contact.title" value="<?= sanitize(gv($pageData,$l,'contact.title')) ?>" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>></div>
                                        <div class="form-group"><label>Subtitle</label><input type="text" class="form-input" data-field="<?= $l ?>.contact.subtitle" value="<?= sanitize(gv($pageData,$l,'contact.subtitle')) ?>" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>></div>
                                    </div>
                                    <div class="form-group"><label>Address</label><input type="text" class="form-input" data-field="<?= $l ?>.contact.address" value="<?= sanitize(gv($pageData,$l,'contact.address')) ?>" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>></div>
                                    <div class="field-row">
                                        <div class="form-group"><label>Working Hours</label><input type="text" class="form-input" data-field="<?= $l ?>.contact.workingHours" value="<?= sanitize(gv($pageData,$l,'contact.workingHours')) ?>" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>></div>
                                        <div class="form-group"><label>Phone</label><input type="text" class="form-input" data-field="<?= $l ?>.contact.phone" value="<?= sanitize(gv($pageData,$l,'contact.phone')) ?>"></div>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="field-row" style="margin-top:12px;">
                                        <div class="form-group"><label>Email</label><input type="email" class="form-input" data-field="en.contact.email" value="<?= sanitize(gv($pageData,'en','contact.email')) ?>"></div>
                                        <div class="form-group"><label>Website</label><input type="text" class="form-input" data-field="en.contact.website" value="<?= sanitize(gv($pageData,'en','contact.website')) ?>"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- ═══ SOCIAL MEDIA ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>📱 <?= $lang === 'ar' ? 'التواصل الاجتماعي' : 'Social Media' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <div class="list-items" id="socialList">
                                        <?php foreach ((gv($pageData, 'en', 'socialMedia.items', []) as $i => $s): ?>
                                            <div class="list-item" data-index="<?= $i ?>">
                                                <div class="list-item-header"><span>#<?= $i + 1 ?></span><button type="button" class="list-item-remove" onclick="removeItem(this)">✕</button></div>
                                                <div class="field-row">
                                                    <input type="text" class="form-input" data-field-array="en.socialMedia.items" data-index="<?= $i ?>" data-key="platform" value="<?= sanitize($s['platform'] ?? '') ?>" placeholder="Platform">
                                                    <input type="text" class="form-input" data-field-array="en.socialMedia.items" data-index="<?= $i ?>" data-key="icon" value="<?= sanitize($s['icon'] ?? '') ?>" placeholder="Icon">
                                                </div>
                                                <div class="field-row" style="margin-top:8px;">
                                                    <input type="text" class="form-input" data-field-array="en.socialMedia.items" data-index="<?= $i ?>" data-key="url" value="<?= sanitize($s['url'] ?? '') ?>" placeholder="URL">
                                                    <input type="text" class="form-input" data-field-array="en.socialMedia.items" data-index="<?= $i ?>" data-key="label" value="<?= sanitize($s['label'] ?? '') ?>" placeholder="Label">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <button type="button" class="add-item-btn" onclick="addSocialLink()">+ <?= $lang === 'ar' ? 'إضافة رابط' : 'Add Link' ?></button>
                                </div>
                            </div>

                            <!-- ═══ FOOTER ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>📎 <?= $lang === 'ar' ? 'التذييل' : 'Footer' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <?php foreach (['en', 'ar'] as $l): ?>
                                    <div class="bilingual-label">🌐 <?= $l === 'en' ? 'English' : 'العربية' ?></div>
                                    <div class="form-group"><label>Copyright</label><input type="text" class="form-input" data-field="<?= $l ?>.footer.copyright" value="<?= sanitize(gv($pageData,$l,'footer.copyright')) ?>" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>></div>
                                    <div class="form-group">
                                        <label>Links</label>
                                        <div class="list-items" id="footerLinks_<?= $l ?>">
                                            <?php foreach ((gv($pageData, $l, 'footer.links', []) as $i => $link): ?>
                                                <div class="list-item" data-index="<?= $i ?>">
                                                    <div class="list-item-header"><span>#<?= $i + 1 ?></span><button type="button" class="list-item-remove" onclick="removeItem(this)">✕</button></div>
                                                    <div class="field-row">
                                                        <input type="text" class="form-input" data-field-array="<?= $l ?>.footer.links" data-index="<?= $i ?>" data-key="label" value="<?= sanitize($link['label'] ?? '') ?>" placeholder="Label" <?= $l === 'ar' ? 'dir="rtl"' : '' ?>>
                                                        <input type="text" class="form-input" data-field-array="<?= $l ?>.footer.links" data-index="<?= $i ?>" data-key="url" value="<?= sanitize($link['url'] ?? '') ?>" placeholder="URL">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" class="add-item-btn" onclick="addFooterLink('<?= $l ?>')">+ <?= $l === 'en' ? 'Add Link' : 'إضافة رابط' ?></button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ═══ THEME COLORS ═══ -->
                            <div class="section-panel">
                                <div class="section-header" onclick="toggleSection(this)"><h3>🎨 <?= $lang === 'ar' ? 'ألوان السمة' : 'Theme Colors' ?></h3><span class="toggle">▼</span></div>
                                <div class="section-body">
                                    <div class="form-group">
                                        <label><?= $lang === 'ar' ? 'سمات جاهزة' : 'Preset Themes' ?></label>
                                        <div class="preset-colors">
                                            <div class="preset-color" style="background:#006B6B;" onclick="applyPreset('teal')" title="Teal"></div>
                                            <div class="preset-color" style="background:#0f1a2a;" onclick="applyPreset('dark')" title="Dark"></div>
                                            <div class="preset-color" style="background:#1a5276;" onclick="applyPreset('blue')" title="Blue"></div>
                                            <div class="preset-color" style="background:#1a2a3a;" onclick="applyPreset('navy')" title="Navy"></div>
                                        </div>
                                    </div>
                                    <?php
                                    $colors = [
                                        'primaryColor' => 'Primary', 'secondaryColor' => 'Secondary', 'accentColor' => 'Accent',
                                        'goldAccent' => 'Gold', 'backgroundColor' => 'Background', 'surfaceColor' => 'Surface',
                                        'textPrimary' => 'Text Primary', 'textSecondary' => 'Text Secondary',
                                        'gradientStart' => 'Gradient Start', 'gradientEnd' => 'Gradient End'
                                    ];
                                    $theme = $pageData['theme'] ?? [];
                                    foreach ($colors as $key => $label):
                                        $val = $theme[$key] ?? '#006B6B';
                                    ?>
                                    <div class="field-row" style="margin-bottom:10px;">
                                        <div class="form-group">
                                            <label><?= $label ?></label>
                                            <div class="color-field">
                                                <input type="color" class="color-swatch" data-field="theme.<?= $key ?>" value="<?= sanitize($val) ?>">
                                                <input type="text" class="form-input" data-field="theme.<?= $key ?>" value="<?= sanitize($val) ?>">
                                            </div>
                                        </div>
                                    <?php if (($i = array_search($key, array_keys($colors))) !== false && $i % 2 === 0 && isset(array_values($colors)[$i+1])): ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                    <div class="theme-preview">
                                        <?php foreach ($colors as $key => $label): ?>
                                            <div class="theme-preview-swatch" id="preview_<?= $key ?>" style="background:<?= $theme[$key] ?? '#006B6B' ?>;"></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="editor-sidebar">
                            <div class="section-panel">
                                <div class="section-header"><h3>⚡ <?= $lang === 'ar' ? 'إجراءات' : 'Actions' ?></h3></div>
                                <div class="section-body">
                                    <div class="quick-actions">
                                        <a href="../" target="_blank" class="quick-action"><span class="qa-icon">🌐</span><?= $lang === 'ar' ? 'معاينة' : 'Preview' ?></a>
                                        <a href="?action=dashboard" class="quick-action"><span class="qa-icon">📊</span><?= $lang === 'ar' ? 'لوحة التحكم' : 'Dashboard' ?></a>
                                        <button type="submit" class="quick-action" style="border:none;cursor:pointer;font-family:var(--font);"><span class="qa-icon">💾</span><?= $lang === 'ar' ? 'حفظ' : 'Save' ?></button>
                                        <button type="button" class="quick-action" style="border:none;cursor:pointer;font-family:var(--font);" onclick="exportJSON()"><span class="qa-icon">📤</span>JSON</button>
                                    </div>
                                </div>
                            </div>
                            <div class="section-panel">
                                <div class="section-header"><h3>ℹ️ <?= $lang === 'ar' ? 'معلومات' : 'Info' ?></h3></div>
                                <div class="section-body">
                                    <div style="font-size:12px;color:var(--text-secondary);">
                                        <p style="margin-bottom:4px;"><strong>File:</strong> <?= sanitize($pageName) ?>.json</p>
                                        <p style="margin-bottom:4px;"><strong>Modified:</strong> <?= date('M d, Y H:i', filemtime(CONTENT_DIR . '/' . $pageName . '.json')) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="section-panel">
                                <div class="section-header"><h3>🎨 <?= $lang === 'ar' ? 'سمات سريعة' : 'Quick Themes' ?></h3></div>
                                <div class="section-body">
                                    <button type="button" class="btn btn-secondary btn-sm btn-full" style="margin-bottom:6px;" onclick="applyPreset('teal')">🟢 Teal Green</button>
                                    <button type="button" class="btn btn-secondary btn-sm btn-full" style="margin-bottom:6px;" onclick="applyPreset('dark')">⬛ Dark Navy</button>
                                    <button type="button" class="btn btn-secondary btn-sm btn-full" style="margin-bottom:6px;" onclick="applyPreset('blue')">🔵 Professional Blue</button>
                                    <button type="button" class="btn btn-secondary btn-sm btn-full" onclick="applyPreset('navy')">🟦 Navy</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- JSON Tab -->
            <div id="tab-json" class="tab-content">
                <form method="POST" action="?action=save" id="jsonForm">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="page" value="<?= sanitize($pageName) ?>">
                    <input type="hidden" name="content" id="jsonRawContent">
                    <div class="json-editor-wrap">
                        <div class="json-editor-header">
                            <h3>{ } Raw JSON</h3>
                            <div style="display:flex;gap:8px;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="formatJSON()">✨ Format</button>
                                <button type="submit" class="btn btn-primary btn-sm">💾 Save</button>
                            </div>
                        </div>
                        <textarea class="json-textarea" id="jsonTextarea" spellcheck="false"><?= json_encode($pageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></textarea>
                    </div>
                </form>
            </div>
        </div>
<?php elseif ($action === 'images'): ?>
        <div class="topbar">
            <h1 class="topbar-title">📷 <?= $lang === 'ar' ? 'معرض الصور' : 'Image Gallery' ?></h1>
            <div class="topbar-actions">
                <a href="?action=dashboard" class="btn btn-secondary btn-sm">← <?= $lang === 'ar' ? 'رجوع' : 'Back' ?></a>
            </div>
        </div>
        <div class="content-area">
            <?php if ($msg = getFlash('success')): ?><div class="flash flash-success">✅ <?= sanitize($msg) ?></div><?php endif; ?>
            <?php if ($msg = getFlash('error')): ?><div class="flash flash-error">⚠ <?= sanitize($msg) ?></div><?php endif; ?>

            <div class="section-panel" style="margin-bottom:20px;">
                <div class="section-header"><h3>📤 <?= $lang === 'ar' ? 'رفع صورة جديدة' : 'Upload New Image' ?></h3></div>
                <div class="section-body">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <input type="file" id="galleryUploadInput" accept="image/jpeg,image/png,image/gif,image/webp" style="font-size:13px;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="uploadGalleryImage()">📤 <?= $lang === 'ar' ? 'رفع' : 'Upload' ?></button>
                        <span id="galleryUploadStatus" style="font-size:12px;color:var(--text-muted);"></span>
                    </div>
                    <div class="field-hint" style="margin-top:8px;"><?= $lang === 'ar' ? 'الصيغ المدعومة: JPG, PNG, GIF, WebP — الحد الأقصى: 5MB' : 'Supported: JPG, PNG, GIF, WebP — Max: 5MB' ?></div>
                </div>
            </div>

            <div class="section-panel">
                <div class="section-header"><h3>🖼 <?= $lang === 'ar' ? 'جميع الصور' : 'All Images' ?></h3></div>
                <div class="section-body">
                    <?php
                    $images = [];
                    if (is_dir(UPLOAD_DIR)) {
                        $files = glob(UPLOAD_DIR . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
                        usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
                        foreach ($files as $f) {
                            $images[] = [
                                'filename' => basename($f),
                                'url' => '../uploads/' . basename($f),
                                'size' => filesize($f),
                                'modified' => filemtime($f)
                            ];
                        }
                    }
                    ?>
                    <?php if (empty($images)): ?>
                        <div class="empty-state">
                            <div class="icon">🖼</div>
                            <h3><?= $lang === 'ar' ? 'لا توجد صور' : 'No Images Yet' ?></h3>
                            <p><?= $lang === 'ar' ? 'ارفع أول صورة للبدء' : 'Upload your first image to get started.' ?></p>
                        </div>
                    <?php else: ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
                            <?php foreach ($images as $img): ?>
                                <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;">
                                    <div style="height:140px;overflow:hidden;background:#f0f2f5;display:flex;align-items:center;justify-content:center;">
                                        <img src="<?= sanitize($img['url']) ?>" alt="<?= sanitize($img['filename']) ?>" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                                    </div>
                                    <div style="padding:10px;">
                                        <div style="font-size:11px;color:var(--text-muted);word-break:break-all;margin-bottom:6px;"><?= sanitize($img['filename']) ?></div>
                                        <div style="font-size:10px;color:var(--text-muted);margin-bottom:8px;"><?= round($img['size'] / 1024) ?>KB — <?= date('M d, Y', $img['modified']) ?></div>
                                        <div style="display:flex;gap:4px;">
                                            <button type="button" class="btn btn-secondary btn-sm" style="flex:1;font-size:11px;" onclick="copyToClipboard('<?= sanitize($img['url']) ?>', this)">📋 <?= $lang === 'ar' ? 'نسخ الرابط' : 'Copy URL' ?></button>
                                            <form method="POST" action="?action=delete_image" onsubmit="return confirm('<?= $lang === 'ar' ? 'حذف هذه الصورة؟' : 'Delete this image?' ?>')" style="margin:0;">
                                                <input type="hidden" name="filename" value="<?= sanitize($img['filename']) ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" style="font-size:11px;">🗑</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php endif; ?>
    </div>
</div>

<!-- Create Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header"><h3>➕ <?= $lang === 'ar' ? 'إنشاء صفحة' : 'Create Page' ?></h3><button class="modal-close" onclick="closeCreateModal()">✕</button></div>
        <form method="POST" action="?action=create">
            <div class="modal-body">
                <div class="form-group"><label><?= $lang === 'ar' ? 'الاسم (رابط URL)' : 'Page Name (slug)' ?></label><input type="text" name="page_name" class="form-input" placeholder="e.g., services" required pattern="[a-zA-Z0-9_-]+"><div class="field-hint">Becomes: content/your-name.json</div></div>
                <div class="form-group"><label><?= $lang === 'ar' ? 'العنوان (إنجليزي)' : 'Title (EN)' ?></label><input type="text" name="page_title_en" class="form-input" placeholder="e.g., Our Services"></div>
                <div class="form-group"><label><?= $lang === 'ar' ? 'العنوان (عربي)' : 'Title (AR)' ?></label><input type="text" name="page_title" class="form-input" placeholder="e.g., خدماتنا" dir="rtl"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateModal()"><?= $lang === 'ar' ? 'إلغاء' : 'Cancel' ?></button>
                <button type="submit" class="btn btn-primary"><?= $lang === 'ar' ? 'إنشاء' : 'Create' ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    event.target.classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
    if (tab === 'json') syncToJSON();
}

function toggleSection(h) { h.nextElementSibling.classList.toggle('hidden'); h.classList.toggle('collapsed'); }
function openCreateModal() { document.getElementById('createModal').classList.add('active'); document.querySelector('#createModal input[name="page_name"]').focus(); }
function closeCreateModal() { document.getElementById('createModal').classList.remove('active'); }
document.getElementById('createModal')?.addEventListener('click', function(e) { if (e.target === this) closeCreateModal(); });

const defaultData = <?= json_encode($pageData ?? ['en'=>[],'ar'=>[],'theme'=>['primaryColor'=>'#006B6B']], JSON_UNESCAPED_UNICODE) ?>;
let pageData = JSON.parse(JSON.stringify(defaultData));

function setNestedValue(obj, path, value) {
    const parts = path.split('.');
    let cur = obj;
    for (let i = 0; i < parts.length - 1; i++) {
        if (!(parts[i] in cur)) cur[parts[i]] = {};
        cur = cur[parts[i]];
    }
    cur[parts[parts.length - 1]] = value;
}

function getNestedValue(obj, path) {
    return path.split('.').reduce((o, k) => o && o[k], obj);
}

function collectFormData() {
    document.querySelectorAll('[data-field]').forEach(el => {
        setNestedValue(pageData, el.getAttribute('data-field'), el.value);
    });
    document.querySelectorAll('[data-field-array]').forEach(el => {
        const arrayPath = el.getAttribute('data-field-array');
        const index = parseInt(el.getAttribute('data-index'));
        const key = el.getAttribute('data-key');
        let array = getNestedValue(pageData, arrayPath);
        if (!Array.isArray(array)) { array = []; setNestedValue(pageData, arrayPath, array); }
        if (!array[index]) array[index] = {};
        array[index][key] = el.value;
    });
    return pageData;
}

function syncToJSON() { collectFormData(); document.getElementById('jsonTextarea').value = JSON.stringify(pageData, null, 2); }

function formatJSON() {
    try { document.getElementById('jsonTextarea').value = JSON.stringify(JSON.parse(document.getElementById('jsonTextarea').value), null, 2); }
    catch (e) { alert('Invalid JSON: ' + e.message); }
}

function exportJSON() {
    collectFormData();
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([JSON.stringify(pageData, null, 2)], { type: 'application/json' }));
    a.download = '<?= $pageName ?>.json';
    a.click();
}

function escapeHTML(s) { return !s ? '' : s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

// Dynamic item counters
let counters = {};
function getCount(l, type) { const k = l + '_' + type; if (!(k in counters)) counters[k] = (getNestedValue(pageData, l + '.' + type + '.items') || []).length; return counters[k]; }
function incCount(l, type) { const k = l + '_' + type; counters[k] = getCount(l, type) + 1; }

function addStat(l) { collectFormData(); if (!pageData[l].stats) pageData[l].stats = []; pageData[l].stats.push({ icon: '📊', value: '', label: '' }); rebuildList(l, 'stats', ['icon','value','label'], ['Icon','Value','Label'], false); }
function addDivision(l) { collectFormData(); if (!pageData[l].divisions) pageData[l].divisions = {items:[]}; if (!pageData[l].divisions.items) pageData[l].divisions.items = []; pageData[l].divisions.items.push({ icon: '🏢', title: '', arabic: '', description: '', link: '#', btnText: l==='en'?'Learn More':'اعرف المزيد', status: 'active', image: '' }); rebuildList(l, 'divisions.items', ['icon','title','arabic','description','link','btnText','status','image'], ['Icon','Title','Arabic','Desc','Link','Btn','Status','Image'], true); }
function addService(l) { collectFormData(); if (!pageData[l].services) pageData[l].services = {items:[]}; if (!pageData[l].services.items) pageData[l].services.items = []; pageData[l].services.items.push({ icon: '🔧', title: '', description: '', link: '#', image: '' }); rebuildList(l, 'services.items', ['icon','title','description','link','image'], ['Icon','Title','Desc','Link','Image'], true); }
function addTeamMember(l) { collectFormData(); if (!pageData[l].team) pageData[l].team = {items:[]}; if (!pageData[l].team.items) pageData[l].team.items = []; pageData[l].team.items.push({ name: '', position: '', bio: '', image: '', email: '', linkedin: '' }); rebuildList(l, 'team.items', ['name','position','bio','image','email','linkedin'], ['Name','Position','Bio','Image','Email','LinkedIn'], true); }
function addSocialLink() { collectFormData(); if (!pageData.en.socialMedia) pageData.en.socialMedia = {items:[]}; if (!pageData.en.socialMedia.items) pageData.en.socialMedia.items = []; pageData.en.socialMedia.items.push({ platform: '', url: '', icon: '🔗', label: '' }); rebuildSocial(); }
function addFooterLink(l) { collectFormData(); if (!pageData[l].footer) pageData[l].footer = {links:[]}; if (!pageData[l].footer.links) pageData[l].footer.links = []; pageData[l].footer.links.push({ label: '', url: '' }); rebuildFooterLinks(l); }

function removeItem(btn) {
    collectFormData();
    const item = btn.closest('.list-item');
    const container = item.closest('.list-items');
    const f = item.querySelector('[data-field-array]');
    if (f) {
        const path = f.getAttribute('data-field-array');
        const idx = parseInt(item.getAttribute('data-index'));
        let arr = getNestedValue(pageData, path);
        if (Array.isArray(arr)) arr.splice(idx, 1);
    }
    // Rebuild all lists for this container
    rebuildAllFromContainer(container);
}

function rebuildAllFromContainer(container) {
    // Identify type from container ID
    const id = container.id;
    if (!id) return;
    const parts = id.split('_');
    const lang = parts[0];
    const type = parts.slice(1).join('_');
    
    if (type === 'stats') { rebuildList(lang, 'stats', ['icon','value','label'], ['Icon','Value','Label'], false); }
    else if (type === 'divisions') { rebuildList(lang, 'divisions.items', ['icon','title','arabic','description','link','btnText','status','image'], ['Icon','Title','Arabic','Desc','Link','Btn','Status','Image'], true); }
    else if (type === 'services') { rebuildList(lang, 'services.items', ['icon','title','description','link','image'], ['Icon','Title','Desc','Link','Image'], true); }
    else if (type === 'team') { rebuildList(lang, 'team.items', ['name','position','bio','image','email','linkedin'], ['Name','Position','Bio','Image','Email','LinkedIn'], true); }
    else if (type === 'social') { rebuildSocial(); }
    else if (type.startsWith('footerLinks')) { rebuildFooterLinks(lang); }
}

function rebuildList(l, path, keys, labels, isNested) {
    const id = l + '_' + (isNested ? path.replace('.items','') : path);
    const container = document.getElementById(id);
    if (!container) return;
    const items = getNestedValue(pageData, path) || [];
    container.innerHTML = '';
    items.forEach((item, i) => {
        let html = `<div class="list-item" data-index="${i}"><div class="list-item-header"><span>#${i+1}</span><button type="button" class="list-item-remove" onclick="removeItem(this)">✕</button></div><div class="field-row">`;
        const rtl = l === 'ar' ? ' dir="rtl"' : '';
        keys.forEach((k, ki) => {
            const fullKey = isNested ? path.replace('.items','.'+k) : path + '.' + k;
            const val = item[k] || '';
            if (k === 'status') {
                html += `</div><div class="field-row" style="margin-top:8px;"><select class="form-input" data-field-array="${l}.${path}" data-index="${i}" data-key="status"><option value="active" ${val==='active'?'selected':''}>Active</option><option value="coming_soon" ${val==='coming_soon'?'selected':''}>Coming Soon</option><option value="inactive" ${val==='inactive'?'selected':''}>Inactive</option></select>`;
            } else if (k === 'description' || k === 'bio') {
                html += `</div><textarea class="form-input" style="margin-top:8px;" rows="2" data-field-array="${l}.${path}" data-index="${i}" data-key="${k}" placeholder="${labels[ki]}"${rtl}>${escapeHTML(val)}</textarea>`;
            } else {
                const arrPath = isNested ? `${l}.${path}` : `${l}.${path}`;
                html += `<input type="text" class="form-input" data-field-array="${arrPath}" data-index="${i}" data-key="${k}" value="${escapeHTML(val)}" placeholder="${labels[ki]}"${rtl}>`;
                if (ki % 2 === 0 && ki < keys.length - 1 && keys[ki+1] !== 'description' && keys[ki+1] !== 'bio' && keys[ki+1] !== 'status') { /* continue */ }
                else { html += `</div><div class="field-row" style="margin-top:8px;">`; }
            }
        });
        html += `</div></div>`;
        container.innerHTML += html;
    });
}

function rebuildSocial() {
    const container = document.getElementById('socialList');
    if (!container) return;
    const items = getNestedValue(pageData, 'en.socialMedia.items') || [];
    container.innerHTML = '';
    items.forEach((s, i) => {
        container.innerHTML += `<div class="list-item" data-index="${i}"><div class="list-item-header"><span>#${i+1}</span><button type="button" class="list-item-remove" onclick="removeItem(this)">✕</button></div><div class="field-row"><input type="text" class="form-input" data-field-array="en.socialMedia.items" data-index="${i}" data-key="platform" value="${escapeHTML(s.platform)}" placeholder="Platform"><input type="text" class="form-input" data-field-array="en.socialMedia.items" data-index="${i}" data-key="icon" value="${escapeHTML(s.icon)}" placeholder="Icon"></div><div class="field-row" style="margin-top:8px;"><input type="text" class="form-input" data-field-array="en.socialMedia.items" data-index="${i}" data-key="url" value="${escapeHTML(s.url)}" placeholder="URL"><input type="text" class="form-input" data-field-array="en.socialMedia.items" data-index="${i}" data-key="label" value="${escapeHTML(s.label)}" placeholder="Label"></div></div>`;
    });
}

function rebuildFooterLinks(l) {
    const container = document.getElementById('footerLinks_' + l);
    if (!container) return;
    const items = getNestedValue(pageData, l + '.footer.links') || [];
    container.innerHTML = '';
    items.forEach((link, i) => {
        const rtl = l === 'ar' ? ' dir="rtl"' : '';
        container.innerHTML += `<div class="list-item" data-index="${i}"><div class="list-item-header"><span>#${i+1}</span><button type="button" class="list-item-remove" onclick="removeItem(this)">✕</button></div><div class="field-row"><input type="text" class="form-input" data-field-array="${l}.footer.links" data-index="${i}" data-key="label" value="${escapeHTML(link.label)}" placeholder="Label"${rtl}><input type="text" class="form-input" data-field-array="${l}.footer.links" data-index="${i}" data-key="url" value="${escapeHTML(link.url)}" placeholder="URL"></div></div>`;
    });
}

// Theme Presets
const presets = {
    teal: { primaryColor:'#006B6B', secondaryColor:'#004D4D', accentColor:'#C8A951', backgroundColor:'#F5F7FA', surfaceColor:'#FFFFFF', textPrimary:'#1A1A2E', textSecondary:'#4A5568', textMuted:'#A0AEC0', gradientStart:'#006B6B', gradientEnd:'#00897B', goldAccent:'#C8A951' },
    dark: { primaryColor:'#0f1a2a', secondaryColor:'#1a2a3a', accentColor:'#c9a84c', backgroundColor:'#0a0f1a', surfaceColor:'#141e2e', textPrimary:'#ffffff', textSecondary:'#a0b4cc', textMuted:'#637d96', gradientStart:'#0f1a2a', gradientEnd:'#1a2a3a', goldAccent:'#c9a84c' },
    blue: { primaryColor:'#1a5276', secondaryColor:'#154360', accentColor:'#d4ac0d', backgroundColor:'#eaf2f8', surfaceColor:'#ffffff', textPrimary:'#1a1a2e', textSecondary:'#4a5568', textMuted:'#a0aec0', gradientStart:'#1a5276', gradientEnd:'#2980b9', goldAccent:'#d4ac0d' },
    navy: { primaryColor:'#1a2a3a', secondaryColor:'#0f1a2a', accentColor:'#c9a84c', backgroundColor:'#f0f2f5', surfaceColor:'#ffffff', textPrimary:'#1a1a2e', textSecondary:'#4a5568', textMuted:'#a0aec0', gradientStart:'#1a2a3a', gradientEnd:'#2c3e50', goldAccent:'#c9a84c' }
};

function applyPreset(name) {
    const p = presets[name]; if (!p) return;
    collectFormData();
    if (!pageData.theme) pageData.theme = {};
    Object.assign(pageData.theme, p);
    document.querySelectorAll('[data-field^="theme."]').forEach(el => {
        const f = el.getAttribute('data-field');
        const v = getNestedValue(pageData, f);
        if (v !== undefined) el.value = v;
    });
    updatePreview();
}

function updatePreview() {
    const t = pageData.theme || {};
    Object.keys(presets.teal).forEach(k => {
        const el = document.getElementById('preview_' + k);
        if (el && t[k]) el.style.background = t[k];
    });
}

document.getElementById('editorForm')?.addEventListener('submit', function() { collectFormData(); document.getElementById('jsonContent').value = JSON.stringify(pageData); });
document.getElementById('jsonForm')?.addEventListener('submit', function(e) {
    try { JSON.parse(document.getElementById('jsonTextarea').value); document.getElementById('jsonRawContent').value = document.getElementById('jsonTextarea').value; }
    catch (err) { e.preventDefault(); alert('Invalid JSON: ' + err.message); }
});

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const ef = document.getElementById('editorForm');
        const jf = document.getElementById('jsonForm');
        if (ef && document.getElementById('tab-visual')?.classList.contains('active')) { collectFormData(); document.getElementById('jsonContent').value = JSON.stringify(pageData); ef.submit(); }
        else if (jf && document.getElementById('tab-json')?.classList.contains('active')) jf.submit();
    }
    if (e.key === 'Escape') closeCreateModal();
});

// Init color swatches
document.querySelectorAll('.color-swatch').forEach(s => {
    const f = s.getAttribute('data-field');
    const t = document.querySelector(`input.form-input[data-field="${f}"]`);
    if (t) { s.value = t.value; s.addEventListener('input', () => { t.value = s.value; updatePreview(); }); t.addEventListener('input', () => { s.value = t.value; updatePreview(); }); }
});

// ─── Image Upload Functions ───
async function uploadImage(fileInput, targetField) {
    const file = fileInput.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('action', 'upload_image');
    fd.append('image', file);
    try {
        const res = await fetch('index.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.url) {
            // Try data-field selector first (for simple fields like hero)
            const target = document.querySelector('[data-field="' + targetField + '"]');
            if (target) {
                target.value = data.url;
                target.dispatchEvent(new Event('input'));
            } else {
                // For array items, traverse up to the list-item container
                const listItem = fileInput.closest('.list-item');
                if (listItem) {
                    const imgInput = listItem.querySelector('input[data-key="image"]');
                    if (imgInput) {
                        imgInput.value = data.url;
                        imgInput.dispatchEvent(new Event('input'));
                    }
                }
            }
        } else {
            alert(data.error || 'Upload failed');
        }
    } catch (e) { alert('Upload error: ' + e.message); }
}

// Gallery page upload
async function uploadGalleryImage() {
    const input = document.getElementById('galleryUploadInput');
    const status = document.getElementById('galleryUploadStatus');
    const file = input.files[0];
    if (!file) { status.textContent = '⚠ Select a file first'; return; }
    const fd = new FormData();
    fd.append('action', 'upload_image');
    fd.append('image', file);
    status.textContent = '⏳ Uploading...';
    try {
        const res = await fetch('index.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.url) {
            status.textContent = '✅ Uploaded! Reloading...';
            setTimeout(() => location.reload(), 800);
        } else {
            status.textContent = '❌ ' + (data.error || 'Upload failed');
        }
    } catch (e) { status.textContent = '❌ ' + e.message; }
}

// Copy to clipboard helper
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '✅ Copied!';
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    }).catch(() => {
        // Fallback for older browsers
        const ta = document.createElement('textarea');
        ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); document.body.removeChild(ta);
        const orig = btn.innerHTML;
        btn.innerHTML = '✅ Copied!';
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    });
}
</script>
<?php endif; ?>
</body>
</html>
