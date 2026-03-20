<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

$staffID   = (int)$_SESSION['staff_ref'];
$staffName = $_SESSION['staff_name'];
$staffTitle= $_SESSION['staff_title'] ?? '';

// Modules this staff member leads
$modStmt = $pdo->prepare("
    SELECT m.ModuleID, m.ModuleName, m.Description
    FROM Modules m
    WHERE m.ModuleLeaderID = ?
    ORDER BY m.ModuleName
");
$modStmt->execute([$staffID]);
$myModules = $modStmt->fetchAll();

// For each module, get the programmes it appears in
$moduleIDs = array_column($myModules, 'ModuleID');
$moduleMap = [];
if (!empty($moduleIDs)) {
    $placeholders = implode(',', array_fill(0, count($moduleIDs), '?'));
    $progStmt = $pdo->prepare("
        SELECT pm.ModuleID, p.ProgrammeID, p.ProgrammeName, l.LevelName, pm.Year, p.IsPublished
        FROM ProgrammeModules pm
        JOIN Programmes p ON pm.ProgrammeID = p.ProgrammeID
        JOIN Levels l ON p.LevelID = l.LevelID
        WHERE pm.ModuleID IN ($placeholders)
        ORDER BY l.LevelID, p.ProgrammeName, pm.Year
    ");
    $progStmt->execute($moduleIDs);
    foreach ($progStmt->fetchAll() as $row) {
        $moduleMap[$row['ModuleID']][] = $row;
    }
}

// Programmes this staff member leads
$leadStmt = $pdo->prepare("
    SELECT p.*, l.LevelName,
           (SELECT COUNT(*) FROM ProgrammeModules pm WHERE pm.ProgrammeID = p.ProgrammeID) AS ModuleCount,
           (SELECT COUNT(*) FROM InterestedStudents i WHERE i.ProgrammeID = p.ProgrammeID AND i.IsActive = 1) AS StudentCount
    FROM Programmes p
    JOIN Levels l ON p.LevelID = l.LevelID
    WHERE p.ProgrammeLeaderID = ?
    ORDER BY l.LevelID, p.ProgrammeName
");
$leadStmt->execute([$staffID]);
$leadProgrammes = $leadStmt->fetchAll();

// Interested students for programmes this staff member leads
$intStmt = $pdo->prepare("
    SELECT i.StudentName, i.Email, i.RegisteredAt,
           p.ProgrammeName, p.ProgrammeID, l.LevelName, p.LevelID
    FROM InterestedStudents i
    JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
    JOIN Levels l ON p.LevelID = l.LevelID
    WHERE p.ProgrammeLeaderID = ? AND i.IsActive = 1
    ORDER BY p.ProgrammeName, i.StudentName
");
$intStmt->execute([$staffID]);
$interestedStudents = $intStmt->fetchAll();

// Summary counts
$totalModulesLed   = count($myModules);
$totalProgrammesLed = count($leadProgrammes);
$totalStudentsInterested = count($interestedStudents);
$totalProgrammeAppearances = array_sum(array_map('count', $moduleMap));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>My Teaching | Labs Project Staff</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    :root{
      --bg:#f0f4f8;
      --sidebar:#0d47a1;
      --sidebar-dark:#071e4a;
      --accent:#1565c0;
      --accent2:#1976d2;
      --white:#ffffff;
      --text:#1a2332;
      --text-muted:#64748b;
      --border:#e2e8f0;
      --card-shadow:0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    }
    body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex}

    /* ── SIDEBAR ── */
    .sidebar{
      width:260px;flex-shrink:0;
      background:linear-gradient(180deg,var(--sidebar) 0%,var(--sidebar-dark) 100%);
      min-height:100vh;position:sticky;top:0;
      display:flex;flex-direction:column;
      box-shadow:4px 0 24px rgba(0,0,0,.15);
    }
    .sidebar-top{padding:28px 22px 22px;border-bottom:1px solid rgba(255,255,255,.1)}
    .sidebar-logo{color:white;font-size:1.05rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:10px}
    .sidebar-logo .icon-box{width:38px;height:38px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
    .sidebar-logo span{line-height:1.25}
    .sidebar-logo small{display:block;font-size:.67rem;font-weight:400;color:rgba(255,255,255,.45);margin-top:1px;letter-spacing:.3px}
    .staff-badge{
      margin:18px 22px 0;
      padding:12px 14px;
      background:rgba(255,255,255,.08);
      border:1px solid rgba(255,255,255,.12);
      border-radius:12px;
    }
    .staff-badge .avatar{
      width:40px;height:40px;border-radius:50%;
      background:linear-gradient(135deg,#42a5f5,#1565c0);
      display:flex;align-items:center;justify-content:center;
      color:white;font-weight:700;font-size:1rem;margin-bottom:8px;
    }
    .staff-badge .name{color:white;font-size:.9rem;font-weight:600;line-height:1.3}
    .staff-badge .role{color:rgba(255,255,255,.45);font-size:.72rem;margin-top:2px;line-height:1.4}
    .sidebar-nav{padding:20px 14px;flex:1}
    .sidebar-nav a{
      display:flex;align-items:center;gap:10px;
      padding:10px 12px;border-radius:10px;
      color:rgba(255,255,255,.65);text-decoration:none;
      font-size:.88rem;font-weight:500;transition:all .2s;
      border:1px solid transparent;margin-bottom:2px;
    }
    .sidebar-nav a:hover{color:white;background:rgba(255,255,255,.08)}
    .sidebar-nav a.active{color:white;background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.12);font-weight:600}
    .sidebar-nav .nav-icon{font-size:1rem;width:22px;text-align:center}
    .sidebar-footer{padding:16px 14px;border-top:1px solid rgba(255,255,255,.1)}
    .sidebar-footer a{
      display:flex;align-items:center;gap:10px;
      padding:10px 12px;border-radius:10px;
      color:rgba(255,160,160,.75);text-decoration:none;
      font-size:.85rem;font-weight:500;transition:all .2s;
    }
    .sidebar-footer a:hover{color:#ffcdd2;background:rgba(255,100,100,.1)}

    /* ── MAIN ── */
    .main{flex:1;padding:36px 40px;min-width:0}

    /* TOP BAR */
    .topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:12px}
    .topbar-left h1{font-family:'DM Serif Display',serif;font-size:1.9rem;color:var(--text);margin-bottom:3px}
    .topbar-left p{color:var(--text-muted);font-size:.88rem}
    .topbar-right{display:flex;align-items:center;gap:10px}
    .pill{
      display:inline-flex;align-items:center;gap:6px;
      padding:7px 16px;border-radius:30px;font-size:.8rem;font-weight:600;
      background:white;border:1.5px solid var(--border);color:var(--text-muted);
      text-decoration:none;transition:all .2s;
    }
    .pill:hover{border-color:var(--accent);color:var(--accent)}

    /* STAT CARDS */
    .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:36px}
    .stat-card{
      background:white;border-radius:16px;padding:22px 24px;
      box-shadow:var(--card-shadow);border:1.5px solid var(--border);
      transition:transform .2s,box-shadow .2s;position:relative;overflow:hidden;
    }
    .stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(0,0,0,.1)}
    .stat-card::before{
      content:'';position:absolute;top:0;left:0;right:0;height:3px;
      background:linear-gradient(90deg,var(--accent),var(--accent2));
    }
    .stat-card .num{font-size:2.2rem;font-weight:800;color:var(--accent);line-height:1;margin-bottom:6px}
    .stat-card .lbl{font-size:.8rem;color:var(--text-muted);font-weight:500}
    .stat-card .icon{position:absolute;top:18px;right:18px;font-size:1.5rem;opacity:.2}

    /* SECTION HEADER */
    .section-hdr{display:flex;align-items:baseline;gap:12px;margin-bottom:18px}
    .section-hdr h2{font-size:1.15rem;font-weight:700;color:var(--text)}
    .count-pill{
      display:inline-flex;align-items:center;
      background:#e8f0fe;color:var(--accent);
      font-size:.72rem;font-weight:700;
      padding:3px 10px;border-radius:30px;
    }

    /* MODULE CARDS */
    .modules-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;margin-bottom:44px}
    .module-card{
      background:white;border-radius:16px;
      box-shadow:var(--card-shadow);border:1.5px solid var(--border);
      overflow:hidden;transition:transform .25s,box-shadow .25s;
    }
    .module-card:hover{transform:translateY(-4px);box-shadow:0 10px 32px rgba(0,0,0,.1)}
    .module-card-head{
      background:linear-gradient(135deg,#0d47a1,#1976d2);
      padding:20px 22px 16px;
      display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
    }
    .module-card-head h3{color:white;font-size:.98rem;font-weight:700;line-height:1.35;flex:1}
    .mod-icon{
      width:38px;height:38px;flex-shrink:0;
      background:rgba(255,255,255,.15);border-radius:10px;
      display:flex;align-items:center;justify-content:center;
      font-size:1.1rem;
    }
    .module-card-body{padding:18px 22px}
    .mod-desc{
      font-size:.83rem;color:var(--text-muted);line-height:1.65;
      margin-bottom:16px;padding-bottom:14px;
      border-bottom:1px solid var(--border);
    }
    .prog-list-label{font-size:.7rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.7px;margin-bottom:10px}
    .prog-item{
      display:flex;align-items:center;justify-content:space-between;
      padding:8px 12px;border-radius:8px;
      background:#f8fafc;border:1px solid #eef2f7;
      margin-bottom:6px;text-decoration:none;
      transition:all .2s;gap:8px;
    }
    .prog-item:hover{background