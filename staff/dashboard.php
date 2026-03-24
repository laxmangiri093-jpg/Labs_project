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
  <title>My Teaching | Staff Portal</title>
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
    .prog-item:hover{background:#e8f0fe;border-color:#c5d8f8}
    .prog-item-left{display:flex;align-items:center;gap:8px;min-width:0}
    .prog-item-left span{font-size:.83rem;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .prog-item-right{display:flex;align-items:center;gap:6px;flex-shrink:0}
    .year-tag{
      font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:20px;
      background:#dbeafe;color:#1565c0;white-space:nowrap;
    }
    .badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px}
    .badge-ug{background:#e3f2fd;color:#1565c0;border:1px solid #90caf9}
    .badge-pg{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
    .badge-draft{background:#fff3e0;color:#e65100;border:1px solid #ffcc80}
    .no-progs{
      font-size:.82rem;color:var(--text-muted);
      padding:12px;border-radius:8px;
      background:#f8fafc;border:1px dashed var(--border);text-align:center;
    }

    /* PROGRAMME LEADER TABLE */
    .table-box{background:white;border-radius:16px;box-shadow:var(--card-shadow);border:1.5px solid var(--border);overflow:hidden;margin-bottom:44px}
    .table-box table{width:100%;border-collapse:collapse;font-size:.88rem}
    .table-box th{background:#0d47a1;color:white;padding:12px 16px;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
    .table-box td{padding:14px 16px;border-bottom:1px solid var(--border)}
    .table-box tr:last-child td{border-bottom:none}
    .table-box tr:hover td{background:#f8fafc}
    .prog-name-cell{font-weight:600;color:var(--text)}
    .prog-name-cell a{color:var(--accent);text-decoration:none}
    .prog-name-cell a:hover{text-decoration:underline}
    .status-pub{color:#2e7d32;font-weight:600;font-size:.82rem}
    .status-draft{color:#e65100;font-weight:600;font-size:.82rem}
    .empty-state{text-align:center;padding:40px;color:var(--text-muted);font-size:.9rem}

    /* STUDENT TABLE */
    .student-table-box{background:white;border-radius:16px;box-shadow:var(--card-shadow);border:1.5px solid var(--border);overflow:hidden;margin-bottom:44px}
    .student-table-box table{width:100%;border-collapse:collapse;font-size:.88rem}
    .student-table-box th{background:#1b5e20;color:white;padding:12px 16px;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
    .student-table-box td{padding:13px 16px;border-bottom:1px solid var(--border);vertical-align:middle}
    .student-table-box tr:last-child td{border-bottom:none}
    .student-table-box tr:hover td{background:#f1f8f2}
    .student-name{font-weight:600;color:var(--text)}
    .student-email a{color:var(--accent);text-decoration:none;font-size:.85rem}
    .student-email a:hover{text-decoration:underline}
    .reg-date{color:var(--text-muted);font-size:.82rem;white-space:nowrap}
    .filter-row{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border);background:#fafcff;flex-wrap:wrap}
    .filter-row label{font-size:.8rem;font-weight:600;color:var(--text-muted)}
    .filter-row select{padding:6px 12px;border-radius:8px;border:1.5px solid var(--border);background:white;font-family:'DM Sans',sans-serif;font-size:.83rem;color:var(--text);outline:none;cursor:pointer}
    .filter-row select:focus{border-color:var(--accent)}
    .total-note{font-size:.8rem;color:var(--text-muted);margin-left:auto}

    /* RESPONSIVE */
    @media(max-width:1100px){.stats{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:900px){
      .sidebar{display:none}
      .main{padding:24px 20px}
      .modules-grid{grid-template-columns:1fr}
    }
    @media(max-width:600px){.stats{grid-template-columns:1fr 1fr}}
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-top">
    <a href="../index.php" class="sidebar-logo">
      <div class="icon-box">🎓</div>
      <span>Course Hub<small>Staff Portal</small></span>
    </a>
    <div class="staff-badge">
      <div class="avatar"><?= strtoupper(substr($staffName, 3, 1)) ?></div>
      <div class="name"><?= htmlspecialchars($staffName) ?></div>
      <div class="role"><?= htmlspecialchars($staffTitle) ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <a href="dashboard.php" class="active">
      <span class="nav-icon">📋</span> My Teaching
    </a>
    <a href="#students" onclick="document.getElementById('students').scrollIntoView({behavior:'smooth'});return false;">
      <span class="nav-icon">👥</span> Interested Students
    </a>
    <a href="../index.php" target="_blank">
      <span class="nav-icon">🌐</span> View Student Site
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php">
      <span>🚪</span> Sign Out
    </a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">

  <!-- TOP BAR -->
  <div class="topbar">
    <div class="topbar-left">
      <h1>My Teaching Overview</h1>
      <p>Your module responsibilities and programme assignments</p>
    </div>
    <div class="topbar-right">
      <a href="../index.php" class="pill" target="_blank">🌐 View Site</a>
      <a href="logout.php" class="pill" style="color:#e53935;border-color:#ffcdd2;">🚪 Sign Out</a>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats">
    <div class="stat-card">
      <div class="icon">📚</div>
      <div class="num"><?= $totalModulesLed ?></div>
      <div class="lbl">Modules I Lead</div>
    </div>
    <div class="stat-card">
      <div class="icon">🎓</div>
      <div class="num"><?= $totalProgrammeAppearances ?></div>
      <div class="lbl">Programme Appearances</div>
    </div>
    <div class="stat-card">
      <div class="icon">🏆</div>
      <div class="num"><?= $totalProgrammesLed ?></div>
      <div class="lbl">Programmes I Lead</div>
    </div>
    <div class="stat-card">
      <div class="icon">👥</div>
      <div class="num"><?= $totalStudentsInterested ?></div>
      <div class="lbl">Interested Students</div>
    </div>
  </div>

  <!-- MY MODULES -->
  <div class="section-hdr">
    <h2>📚 Modules I Lead</h2>
    <span class="count-pill"><?= $totalModulesLed ?> module<?= $totalModulesLed !== 1 ? 's' : '' ?></span>
  </div>

  <?php if (empty($myModules)): ?>
    <div class="empty-state" style="background:white;border-radius:16px;border:1.5px solid var(--border);margin-bottom:44px;">
      <p style="font-size:2rem;margin-bottom:10px;">📭</p>
      <p>You are not currently assigned as a module leader for any modules.</p>
    </div>
  <?php else: ?>
  <div class="modules-grid">
    <?php foreach ($myModules as $mod): ?>
      <div class="module-card">
        <div class="module-card-head">
          <h3><?= htmlspecialchars($mod['ModuleName']) ?></h3>
          <div class="mod-icon">📚</div>
        </div>
        <div class="module-card-body">
          <p class="mod-desc">
            <?= htmlspecialchars(mb_substr($mod['Description'] ?? 'No description available.', 0, 120)) ?>
            <?= strlen($mod['Description'] ?? '') > 120 ? '…' : '' ?>
          </p>

          <div class="prog-list-label">Appears in Programmes</div>
          <?php $progs = $moduleMap[$mod['ModuleID']] ?? []; ?>
          <?php if (empty($progs)): ?>
            <div class="no-progs">Not assigned to any programme yet</div>
          <?php else: ?>
            <?php foreach ($progs as $pg): ?>
              <div class="prog-item">
                <div class="prog-item-left">
                  <span><?= htmlspecialchars($pg['ProgrammeName']) ?></span>
                </div>
                <div class="prog-item-right">
                  <span class="year-tag">Year <?= (int)$pg['Year'] ?></span>
                  <span class="badge <?= strpos($pg['LevelName'],'Under') !== false ? 'badge-ug' : 'badge-pg' ?>">
                    <?= $pg['LevelName'] === 'Undergraduate' ? 'UG' : 'PG' ?>
                  </span>
                  <?php if (!$pg['IsPublished']): ?>
                    <span class="badge badge-draft">Draft</span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- PROGRAMMES I LEAD -->
  <div class="section-hdr">
    <h2>🏆 Programmes I Lead</h2>
    <span class="count-pill"><?= $totalProgrammesLed ?> programme<?= $totalProgrammesLed !== 1 ? 's' : '' ?></span>
  </div>

  <?php if (empty($leadProgrammes)): ?>
    <div class="empty-state" style="background:white;border-radius:16px;border:1.5px solid var(--border);">
      <p style="font-size:2rem;margin-bottom:10px;">📭</p>
      <p>You are not currently assigned as a programme leader for any programmes.</p>
    </div>
  <?php else: ?>
  <div class="table-box">
    <table>
      <thead>
        <tr>
          <th>Programme</th>
          <th>Level</th>
          <th>Modules</th>
          <th>Interested Students</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leadProgrammes as $lp): ?>
          <tr>
            <td class="prog-name-cell">
              <a href="../programme.php?id=<?= $lp['ProgrammeID'] ?>" target="_blank">
                <?= htmlspecialchars($lp['ProgrammeName']) ?>
              </a>
            </td>
            <td>
              <span class="badge <?= $lp['LevelID'] == 1 ? 'badge-ug' : 'badge-pg' ?>">
                <?= htmlspecialchars($lp['LevelName']) ?>
              </span>
            </td>
            <td><?= (int)$lp['ModuleCount'] ?></td>
            <td><?= (int)$lp['StudentCount'] ?></td>
            <td>
              <?php if ($lp['IsPublished']): ?>
                <span class="status-pub">✔ Published</span>
              <?php else: ?>
                <span class="status-draft">✎ Draft</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <!-- INTERESTED STUDENTS -->
  <div class="section-hdr" id="students" style="scroll-margin-top:30px;">
    <h2>👥 Interested Students</h2>
    <span class="count-pill"><?= $totalStudentsInterested ?> student<?= $totalStudentsInterested !== 1 ? 's' : '' ?></span>
  </div>

  <?php if (empty($leadProgrammes)): ?>
    <div class="empty-state" style="background:white;border-radius:16px;border:1.5px solid var(--border);margin-bottom:44px;">
      <p style="font-size:2rem;margin-bottom:10px;">📭</p>
      <p>You need to be a programme leader to see interested students.</p>
    </div>
  <?php elseif (empty($interestedStudents)): ?>
    <div class="empty-state" style="background:white;border-radius:16px;border:1.5px solid var(--border);margin-bottom:44px;">
      <p style="font-size:2rem;margin-bottom:10px;">📭</p>
      <p>No students have registered interest in your programmes yet.</p>
    </div>
  <?php else: ?>
  <div class="table-box" style="margin-bottom:44px;">
    <div style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border);background:#fafcff;flex-wrap:wrap;">
      <label style="font-size:.8rem;font-weight:600;color:var(--text-muted);">Filter:</label>
      <select id="prog-filter" onchange="filterStudents()"
              style="padding:6px 12px;border-radius:8px;border:1.5px solid var(--border);background:white;font-family:'DM Sans',sans-serif;font-size:.83rem;color:var(--text);outline:none;cursor:pointer;">
        <option value="">All Programmes</option>
        <?php foreach ($leadProgrammes as $lp): ?>
          <option value="prog-<?= $lp['ProgrammeID'] ?>"><?= htmlspecialchars($lp['ProgrammeName']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" id="stu-search" placeholder="Search name or email…" oninput="filterStudents()"
             style="padding:6px 14px;border-radius:8px;border:1.5px solid var(--border);background:white;font-family:'DM Sans',sans-serif;font-size:.83rem;outline:none;width:200px;">
      <span id="filter-count" style="margin-left:auto;font-size:.8rem;color:var(--text-muted);">
        <?= $totalStudentsInterested ?> registration<?= $totalStudentsInterested !== 1 ? 's' : '' ?>
      </span>
    </div>
    <table id="student-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Student Name</th>
          <th>Email Address</th>
          <th>Programme</th>
          <th>Level</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; foreach ($interestedStudents as $s): ?>
          <tr class="stu-row prog-<?= $s['ProgrammeID'] ?>"
              data-name="<?= strtolower(htmlspecialchars($s['StudentName'])) ?>"
              data-email="<?= strtolower(htmlspecialchars($s['Email'])) ?>">
            <td class="row-num" style="color:var(--text-muted);font-size:.82rem;"><?= $i++ ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($s['StudentName']) ?></td>
            <td>
              <a href="mailto:<?= htmlspecialchars($s['Email']) ?>"
                 style="color:var(--accent);text-decoration:none;font-size:.87rem;">
                <?= htmlspecialchars($s['Email']) ?>
              </a>
            </td>
            <td style="font-size:.87rem;"><?= htmlspecialchars($s['ProgrammeName']) ?></td>
            <td>
              <span class="badge <?= $s['LevelID'] == 1 ? 'badge-ug' : 'badge-pg' ?>">
                <?= $s['LevelName'] === 'Undergraduate' ? 'UG' : 'PG' ?>
              </span>
            </td>
            <td style="color:var(--text-muted);font-size:.82rem;white-space:nowrap;">
              <?= date('d M Y', strtotime($s['RegisteredAt'])) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div><!-- /main -->

<script>
function filterStudents() {
  const prog   = document.getElementById('prog-filter').value;
  const search = document.getElementById('stu-search').value.toLowerCase();
  const rows   = document.querySelectorAll('.stu-row');
  let visible  = 0;
  rows.forEach(r => {
    const matchProg   = !prog   || r.classList.contains(prog);
    const matchSearch = !search || r.dataset.name.includes(search) || r.dataset.email.includes(search);
    const show = matchProg && matchSearch;
    r.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  let n = 1;
  rows.forEach(r => { if (r.style.display !== 'none') r.querySelector('.row-num').textContent = n++; });
  document.getElementById('filter-count').textContent =
    `${visible} registration${visible !== 1 ? 's' : ''}`;
}
</script>
</body>
</html>