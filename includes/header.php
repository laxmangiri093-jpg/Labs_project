<?php
// includes/header.php
// Always use absolute __DIR__ paths so this works from any location
$pageTitle = $pageTitle ?? 'Student Course Hub';

// Dynamically work out the base URL from the server path
$scriptPath = $_SERVER['SCRIPT_NAME'];           // e.g. /demo_finalassignment/index.php
$parts      = explode('/', trim($scriptPath,'/')); // ['demo_finalassignment','index.php']
$baseName   = $parts[0];                         // demo_finalassignment
$base       = '/' . $baseName . '/';             // /demo_finalassignment/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?> | Student Course Hub</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/style.css"/>
</head>
<body>

<a href="#main-content" class="skip-link">Skip to main content</a>

<!-- TOP BAR -->
<div class="top-bar">
  <div class="top-bar-inner">
    <span>📧 &nbsp;info@studentcoursehub.ac.uk</span>
    <div>
      <a href="<?= $base ?>index.php?level=1">Undergraduate</a>
      <a href="<?= $base ?>index.php?level=2">Postgraduate</a>
      <a href="<?= $base ?>staff/login.php" style="background:rgba(255,255,255,.12);border-radius:12px;padding:3px 12px;">👤 Staff Portal</a>
      <a href="<?= $base ?>admin/login.php" style="background:rgba(255,255,255,.12);border-radius:12px;padding:3px 12px;">🔒 Admin Login</a>
    </div>
  </div>
</div>

<!-- MAIN NAV -->
<header>
  <div class="header-inner">
    <a href="<?= $base ?>index.php" class="logo">
      <div class="logo-box">🎓</div>
      Student Course Hub
    </a>
    <nav aria-label="Main navigation">
      <ul>
        <li><a href="<?= $base ?>index.php">Home</a></li>
        <li><a href="<?= $base ?>index.php?level=1">Undergraduate</a></li>
        <li><a href="<?= $base ?>index.php?level=2">Postgraduate</a></li>
        <li><a href="<?= $base ?>index.php" class="nav-cta">Browse All</a></li>
      </ul>
    </nav>
  </div>
</header>
