<?php
require_once __DIR__ . '/includes/db.php';

$level  = isset($_GET['level'])  ? (int)$_GET['level']  : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT p.*, l.LevelName, s.Name AS LeaderName, s.JobTitle AS LeaderTitle
        FROM Programmes p
        JOIN Levels l ON p.LevelID = l.LevelID
        JOIN Staff  s ON p.ProgrammeLeaderID = s.StaffID
        WHERE p.IsPublished = 1";
$params = [];
if ($level)  { $sql .= " AND p.LevelID = ?";          $params[] = $level; }
if ($search) { $sql .= " AND p.ProgrammeName LIKE ?"; $params[] = '%'.$search.'%'; }
$sql .= " ORDER BY l.LevelID, p.ProgrammeName";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$programmes = $stmt->fetchAll();

// Helper to mark active links
function isActive($linkLevel, $currentLevel) {
    if ($linkLevel === null && $currentLevel === null) return 'active';
    if ($linkLevel === $currentLevel) return 'active';
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Course Hub</title>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:"Segoe UI",Tahoma,sans-serif;background:#f4f7fb;color:#222;min-height:100vh}

    /* TOP BAR */
    .top-bar{background:#0d47a1;color:rgba(255,255,255,.85);font-size:.82rem;padding:7px 0}
    .top-bar-inner{max-width:1200px;margin:0 auto;padding:0 30px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px}
    .top-bar a{color:rgba(255,255,255,.75);text-decoration:none;margin-left:16px;padding:2px 10px;border-radius:12px;transition:all .25s}
    .top-bar a:hover{color:white;background:rgba(255,255,255,.15)}
    .top-bar a.active{color:white;background:rgba(255,255,255,.25);font-weight:700}

    /* NAV */
    .main-nav{background:white;box-shadow:0 2px 12px rgba(0,0,0,.1);position:sticky;top:0;z-index:1000}
    .nav-inner{max-width:1200px;margin:0 auto;padding:14px 30px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
    .logo{font-size:1.4rem;font-weight:800;color:#0d47a1;text-decoration:none;display:flex;align-items:center;gap:10px}
    .logo-box{width:42px;height:42px;background:#1565c0;border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.2rem;flex-shrink:0}
    .nav-links{list-style:none;display:flex;gap:4px;flex-wrap:wrap;align-items:center}
    .nav-links a{color:#444;text-decoration:none;font-weight:600;font-size:.9rem;padding:7px 14px;border-radius:6px;transition:all .25s}
    .nav-links a:hover{color:#1565c0;background:#e8f0fe}
    .nav-links a.active{color:#1565c0;background:#e8f0fe;font-weight:700}
    .nav-cta{background:#1565c0!important;color:white!important;border-radius:25px!important;padding:7px 18px!important}
    .nav-cta:hover,.nav-cta.active{background:#0d47a1!important;color:white!important}

    /* HERO */
    .hero{background:linear-gradient(135deg,#0d47a1 0%,#1976d2 60%,#1565c0 100%);color:white;padding:80px 30px 70px;position:relative;overflow:hidden}
    .hero::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.07) 1px,transparent 1px);background-size:28px 28px;pointer-events:none}
    .hero-inner{max-width:1200px;margin:0 auto;position:relative;z-index:1}
    .hero h1{font-size:clamp(2.2rem,5vw,3.8rem);font-weight:800;line-height:1.15;margin-bottom:18px}
    .hero h1 span{color:#90caf9}
    .hero p{font-size:1.1rem;opacity:.9;max-width:540px;line-height:1.75;margin-bottom:30px}
    .hero-btns{display:flex;gap:14px;flex-wrap:wrap}
    .btn-white{display:inline-block;padding:11px 28px;border-radius:30px;background:white;color:#1565c0;font-weight:700;text-decoration:none;font-size:.92rem;transition:all .3s;box-shadow:0 4px 14px rgba(0,0,0,.15)}
    .btn-white:hover{background:#e8f0fe;transform:translateY(-2px)}
    .btn-ghost{display:inline-block;padding:11px 28px;border-radius:30px;background:transparent;color:white;border:2px solid rgba(255,255,255,.6);font-weight:700;text-decoration:none;font-size:.92rem;transition:all .3s}
    .btn-ghost:hover,.btn-ghost.active{background:rgba(255,255,255,.2);border-color:white;transform:translateY(-2px)}

    /* STATS */
    .stats-bar{background:white;border-bottom:1px solid #eef0f5}
    .stats-inner{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:repeat(4,1fr)}
    .stat{padding:22px 24px;text-align:center;border-right:1px solid #eef0f5}
    .stat:last-child{border-right:none}
    .stat-num{font-size:2rem;font-weight:800;color:#1565c0}
    .stat-lbl{font-size:.85rem;color:#777;margin-top:3px}

    /* MAIN */
    .container{max-width:1200px;margin:0 auto;padding:0 30px}
    .section{padding:60px 0}
    .section-title{font-size:1.9rem;font-weight:800;color:#0d47a1;margin-bottom:6px}
    .title-bar{display:block;width:50px;height:4px;background:#1976d2;border-radius:4px;margin-top:8px;margin-bottom:14px}
    .section-sub{color:#666;font-size:.95rem;margin-bottom:36px}

    /* FILTER BAR */
    .filter-bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;background:white;padding:18px 22px;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.08);margin-bottom:34px}
    .filter-btn{padding:8px 20px;border-radius:25px;border:2px solid #dde3f0;background:white;color:#666;font-family:"Segoe UI",sans-serif;font-size:.88rem;font-weight:600;cursor:pointer;transition:all .25s}
    .filter-btn:hover{background:#1565c0;border-color:#1565c0;color:white}
    .filter-btn.active{background:#1565c0;border-color:#1565c0;color:white;box-shadow:0 4px 12px rgba(21,101,192,.3)}
    .search-form{display:flex;gap:8px;flex:1;min-width:200px}
    .search-form input{flex:1;padding:9px 18px;border-radius:25px;border:2px solid #dde3f0;background:#f4f7fb;font-family:"Segoe UI",sans-serif;font-size:.9rem;outline:none;transition:all .3s}
    .search-form input:focus{border-color:#1565c0;background:white}
    .btn-search{padding:9px 22px;border-radius:25px;background:#1565c0;color:white;border:none;font-family:"Segoe UI",sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .3s}
    .btn-search:hover{background:#0d47a1}
    .btn-clear{padding:9px 18px;border-radius:25px;background:transparent;color:#1565c0;border:2px solid #1565c0;font-family:"Segoe UI",sans-serif;font-size:.88rem;font-weight:700;text-decoration:none;transition:all .3s}
    .btn-clear:hover{background:#1565c0;color:white}

    /* CARDS */
    .grid{display:grid;gap:26px;grid-template-columns:repeat(auto-fill,minmax(300px,1fr))}
    .card{background:white;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.08);overflow:hidden;text-decoration:none;color:inherit;display:flex;flex-direction:column;transition:transform .3s,box-shadow .3s}
    .card:hover{transform:translateY(-6px);box-shadow:0 12px 36px rgba(0,0,0,.14)}
    .card-placeholder{width:100%;height:185px;background:linear-gradient(135deg,#0d47a1,#1976d2);display:flex;align-items:center;justify-content:center;font-size:3.5rem}
    .card-body{padding:22px;flex:1;display:flex;flex-direction:column}
    .badge{display:inline-block;padding:4px 14px;border-radius:25px;font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
    .badge-ug{background:#e3f2fd;color:#1565c0;border:1.5px solid #90caf9}
    .badge-pg{background:#e8f5e9;color:#2e7d32;border:1.5px solid #a5d6a7}
    .card-title{font-size:1.05rem;font-weight:700;color:#0d47a1;margin:10px 0 8px}
    .card-text{color:#555;font-size:.9rem;line-height:1.7;flex:1}
    .card-meta{margin-top:14px;padding-top:12px;border-top:1px solid #eef0f5;font-size:.82rem;color:#888}

    /* ALERT */
    .alert-info{padding:13px 18px;border-radius:10px;background:#e3f2fd;color:#1565c0;border-left:4px solid #1976d2;margin-bottom:18px}

    /* FOOTER */
    footer{background:linear-gradient(135deg,#0d47a1,#1565c0);color:white;text-align:center;padding:28px 0;margin-top:60px}
    footer p{margin:0;font-size:.92rem;opacity:.9}
    footer a{color:#90caf9;text-decoration:none}
    footer a:hover{text-decoration:underline}

    /* RESPONSIVE */
    @media(max-width:768px){
      .stats-inner{grid-template-columns:repeat(2,1fr)}
      .grid{grid-template-columns:1fr}
      .nav-inner{flex-direction:column;text-align:center}
      .top-bar-inner{flex-direction:column;text-align:center}
      .hero{padding:50px 20px}
      .filter-bar{flex-direction:column;align-items:stretch}
    }
  </style>
</head>
<body>


<div class="top-bar">
  <div class="top-bar-inner">
    <span>📧 &nbsp;info@studentcoursehub.ac.uk</span>
    <div>
      <a href="index.php?level=1" class="<?= $level===1?'active':'' ?>">Undergraduate</a>
      <a href="index.php?level=2" class="<?= $level===2?'active':'' ?>">Postgraduate</a>
      <a href="staff/login.php" style="background:rgba(255,255,255,.12);border-radius:12px;padding:3px 12px;">👤 Staff Portal</a>
      <a href="admin/login.php" style="background:rgba(255,255,255,.12);padding:3px 12px;border-radius:12px;">🔒 Admin Login</a>
    </div>
  </div>
</div>

<!-- MAIN NAV — active class highlights current page/level -->
<div class="main-nav">
  <div class="nav-inner">
    <a href="index.php" class="logo">
      <div class="logo-box">🎓</div>
      Student Course Hub
    </a>
    <ul class="nav-links">
      <li><a href="index.php" class="<?= !$level && !$search ? 'active' : '' ?>">Home</a></li>
      <li><a href="index.php?level=1" class="<?= $level===1?'active':'' ?>">Undergraduate</a></li>
      <li><a href="index.php?level=2" class="<?= $level===2?'active':'' ?>">Postgraduate</a></li>
      <li><a href="index.php" class="nav-cta">Browse All</a></li>
    </ul>
  </div>
</div>

<!-- HERO — hero buttons also highlight active level -->
<section class="hero">
  <div class="hero-inner">
    <h1>Find Your <span>Perfect</span><br>University Programme</h1>
    <p>Explore undergraduate and postgraduate degrees designed to launch your career. Browse modules, meet faculty, and register your interest today.</p>
    <div class="hero-btns">
      <a href="#programmes" class="btn-white">Browse Programmes</a>
      <a href="index.php?level=1" class="btn-ghost <?= $level===1?'active':'' ?>">Undergraduate</a>
      <a href="index.php?level=2" class="btn-ghost <?= $level===2?'active':'' ?>">Postgraduate</a>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats-bar">
  <div class="stats-inner">
    <div class="stat"><div class="stat-num">10</div><div class="stat-lbl">Degree Programmes</div></div>
    <div class="stat"><div class="stat-num">31</div><div class="stat-lbl">Modules Available</div></div>
    <div class="stat"><div class="stat-num">20</div><div class="stat-lbl">Expert Lecturers</div></div>
    <div class="stat"><div class="stat-num">2</div><div class="stat-lbl">Study Levels</div></div>
  </div>
</div>

<!-- PROGRAMMES -->
<main id="main-content">
  <div class="section" id="programmes">
    <div class="container">

      <h2 class="section-title">
        <?php if($level===1): ?>Undergraduate Programmes
        <?php elseif($level===2): ?>Postgraduate Programmes
        <?php else: ?>Available Programmes<?php endif; ?>
      </h2>
      <span class="title-bar"></span>
      <p class="section-sub">
        <?= count($programmes) ?> programme<?= count($programmes)!==1?'s':'' ?> found
        <?= $search?' matching <strong>'.htmlspecialchars($search).'</strong>':'' ?>
      </p>

      <!-- FILTER BUTTONS — active class set by PHP based on $level -->
      <div class="filter-bar">
        <button class="filter-btn <?= !$level?'active':'' ?>"
                onclick="location.href='index.php<?= $search?'?search='.urlencode($search):'' ?>'">
          All
        </button>
        <button class="filter-btn <?= $level===1?'active':'' ?>"
                onclick="location.href='index.php?level=1<?= $search?'&search='.urlencode($search):'' ?>'">
          Undergraduate
        </button>
        <button class="filter-btn <?= $level===2?'active':'' ?>"
                onclick="location.href='index.php?level=2<?= $search?'&search='.urlencode($search):'' ?>'">
          Postgraduate
        </button>

        <form class="search-form" method="GET" action="index.php">
          <?php if($level): ?><input type="hidden" name="level" value="<?= $level ?>"><?php endif; ?>
          <input type="search" name="search"
                 placeholder="Search e.g. Cyber Security, AI..."
                 value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="btn-search">Search</button>
          <?php if($search): ?>
            <a href="index.php<?= $level?'?level='.$level:'' ?>" class="btn-clear">Clear</a>
          <?php endif; ?>
        </form>
      </div>

      <!-- CARDS -->
      <?php if(empty($programmes)): ?>
        <div class="alert-info">No programmes found. <a href="index.php">View all programmes</a></div>
      <?php else: ?>
        <div class="grid">
          <?php foreach($programmes as $p): ?>
            <a href="programme.php?id=<?= $p['ProgrammeID'] ?>" class="card">
              <?php if(!empty($p['Image'])): ?>
                <img src="<?= htmlspecialchars($p['Image']) ?>" alt="<?= htmlspecialchars($p['ProgrammeName']) ?>" style="width:100%;height:185px;object-fit:cover;">
              <?php else: ?>
                <div class="card-placeholder">🎓</div>
              <?php endif; ?>
              <div class="card-body">
                <span class="badge <?= $p['LevelID']==1?'badge-ug':'badge-pg' ?>">
                  <?= htmlspecialchars($p['LevelName']) ?>
                </span>
                <h3 class="card-title"><?= htmlspecialchars($p['ProgrammeName']) ?></h3>
                <p class="card-text"><?= htmlspecialchars(mb_substr($p['Description'],0,110)) ?>...</p>
                <div class="card-meta">
                  👤 <?= htmlspecialchars($p['LeaderName']) ?>
                  <?= $p['LeaderTitle']?' — '.htmlspecialchars($p['LeaderTitle']):'' ?>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</main>

<footer>
  <p>
    &copy; <?= date('Y') ?> Student Course Hub — University of the UK &nbsp;|&nbsp;
    <a href="index.php?level=1">Undergraduate</a> &nbsp;|&nbsp;
    <a href="index.php?level=2">Postgraduate</a>
  </p>
</footer>

</body>
</html>
