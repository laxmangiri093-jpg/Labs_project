<?php
$activePage=$activePage??'';
$pageTitle=$pageTitle??'Admin';
$scriptPath=$_SERVER['SCRIPT_NAME'];
$parts=explode('/',trim($scriptPath,'/'));
$base='/'.$parts[0].'/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?> | Labs Project Admin</title>
  <link rel="stylesheet" href="<?= $base ?>assets/css/style.css"/>
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <a href="dashboard.php" class="sidebar-logo">🎓 Labs Project<span>Administration Panel</span></a>
    <nav class="sidebar-nav">
      <a href="dashboard.php"  class="<?= $activePage==='dashboard' ?'active':'' ?>">📊 &nbsp;Dashboard</a>
      <a href="programmes.php" class="<?= $activePage==='programmes'?'active':'' ?>">🎓 &nbsp;Programmes</a>
      <a href="modules.php"    class="<?= $activePage==='modules'   ?'active':'' ?>">📚 &nbsp;Modules</a>
      <a href="students.php"       class="<?= $activePage==='students'      ?'active':'' ?>">👥 &nbsp;Students</a>
      <a href="staff_accounts.php" class="<?= $activePage==='staff_accounts'?'active':'' ?>">🔑 &nbsp;Staff Accounts</a>
      <a href="<?= $base ?>index.php" target="_blank" style="margin-top:30px;border-top:1px solid rgba(255,255,255,.1);padding-top:14px;">🌐 &nbsp;View Site</a>
      <a href="logout.php" style="color:rgba(255,160,160,.85);">🚪 &nbsp;Logout</a>
    </nav>
  </aside>
  <main class="admin-main">