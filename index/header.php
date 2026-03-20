<?php
// includes/header.php
$pageTitle = $pageTitle ?? 'Labs Project';

// Dynamically work out the base URL from the server path
$scriptPath = $_SERVER['SCRIPT_NAME'];           // e.g. /Labs_project/index.php
$parts      = explode('/', trim($scriptPath,'/')); // ['Labs_project','index.php']
$baseName   = $parts[0];                         // Labs_project
$base       = '/' . $baseName . '/';             // /Labs_project/
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?> | Labs Project</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/style.css"/>
</head>
<body>

<a href="#main-content" class="skip-link">Skip to main content</a>

<!-- TOP BAR -->
<div class="top-bar">
  <div class="top-bar-inner">
    <span>📧 &nbsp;info@labsproject.ac.uk</span>
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
      Labs Project
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