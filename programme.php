<?php
require_once __DIR__ . '/includes/db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*,l.LevelName,s.Name AS LeaderName,s.JobTitle AS LeaderTitle
  FROM Programmes p JOIN Levels l ON p.LevelID=l.LevelID JOIN Staff s ON p.ProgrammeLeaderID=s.StaffID
  WHERE p.ProgrammeID=? AND p.IsPublished=1");
$stmt->execute([$id]);
$prog = $stmt->fetch();
if(!$prog) { header('Location: index.php'); exit; }

$mStmt = $pdo->prepare("SELECT m.*,pm.Year,s.Name AS ModLeader,s.JobTitle AS ModLeaderTitle
  FROM ProgrammeModules pm JOIN Modules m ON pm.ModuleID=m.ModuleID JOIN Staff s ON m.ModuleLeaderID=s.StaffID
  WHERE pm.ProgrammeID=? ORDER BY pm.Year,m.ModuleName");
$mStmt->execute([$id]);
$byYear = [];
foreach($mStmt->fetchAll() as $m) $byYear[$m['Year']][] = $m;

$success=$error='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['register'])) {
  $name  = trim($_POST['name']  ?? '');
  $email = trim($_POST['email'] ?? '');
  if(!$name||!$email)          { $error='Please fill in your name and email.'; }
  elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)) { $error='Please enter a valid email address.'; }
  else {
    $safeName  = htmlspecialchars($name,  ENT_QUOTES,'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES,'UTF-8');
    try {
      $pdo->prepare("INSERT INTO InterestedStudents (ProgrammeID,StudentName,Email) VALUES (?,?,?)")->execute([$id,$safeName,$safeEmail]);
      $success = "Thank you, $safeName! We will contact you about ".htmlspecialchars($prog['ProgrammeName']).".";
    } catch(PDOException $e) {
      $error = $e->getCode()==='23000' ? 'This email is already registered for this programme.' : 'Something went wrong. Please try again.';
    }
  }
}

$yearNames=[1=>'Year One',2=>'Year Two',3=>'Year Three',4=>'Year Four'];
$pageTitle = $prog['ProgrammeName'];
require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="section">
<div class="container">

  <p style="margin-bottom:20px;"><a href="index.php" style="color:#1565c0;font-weight:600;">&#8592; Back to all programmes</a></p>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:start;margin-bottom:50px;" class="prog-header">
    <div>
      <span class="badge <?= $prog['LevelID']==1?'badge-ug':'badge-pg' ?>" style="margin-bottom:12px;display:inline-block;"><?= htmlspecialchars($prog['LevelName']) ?></span>
      <h1 style="color:#0d47a1;font-size:2rem;font-weight:800;line-height:1.3;margin-bottom:14px;"><?= htmlspecialchars($prog['ProgrammeName']) ?></h1>
      <p style="color:#555;line-height:1.8;margin-bottom:22px;"><?= htmlspecialchars($prog['Description']) ?></p>
      <div class="staff-card" style="margin-bottom:24px;">
        <div class="staff-avatar"><?= strtoupper(substr($prog['LeaderName'],3,1)) ?></div>
        <div class="staff-info">
          <p style="font-size:.74rem;color:#aaa;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;">Programme Leader</p>
          <h4><?= htmlspecialchars($prog['LeaderName']) ?></h4>
          <?php if($prog['LeaderTitle']): ?><p><?= htmlspecialchars($prog['LeaderTitle']) ?></p><?php endif; ?>
        </div>
      </div>
      <a href="#register" class="btn btn-primary">Register My Interest</a>
    </div>
    <?php if(!empty($prog['Image'])): ?>
      <img src="<?= htmlspecialchars($prog['Image']) ?>" alt="<?= htmlspecialchars($prog['ProgrammeName']) ?>" style="width:100%;height:280px;object-fit:cover;border-radius:16px;">
    <?php else: ?>
      <div class="card-placeholder" style="height:280px;border-radius:16px;" aria-hidden="true">🎓</div>
    <?php endif; ?>
  </div>

  <section aria-labelledby="mod-heading">
    <h2 class="section-title" id="mod-heading">Modules by Year <span></span></h2>
    <p class="section-sub">What you will study throughout this programme</p>
    <?php foreach($byYear as $year=>$mods): ?>
      <div style="margin-bottom:36px;">
        <div class="year-label"><?= $yearNames[$year]??'Year '.$year ?></div>
        <div class="grid grid-3">
          <?php foreach($mods as $mod): ?>
            <div class="card" style="cursor:default;">
              <?php if(!empty($mod['Image'])): ?>
                <img src="<?= htmlspecialchars($mod['Image']) ?>" alt="<?= htmlspecialchars($mod['ModuleName']) ?>" style="width:100%;height:110px;object-fit:cover;">
              <?php else: ?>
                <div class="card-placeholder" style="height:110px;" aria-hidden="true">📚</div>
              <?php endif; ?>
              <div class="card-body">
                <h3 class="card-title" style="font-size:.95rem;"><?= htmlspecialchars($mod['ModuleName']) ?></h3>
                <p class="card-text" style="font-size:.85rem;"><?= htmlspecialchars(mb_substr($mod['Description']??'',0,90)) ?>...</p>
                <div class="card-meta">👤 <?= htmlspecialchars($mod['ModLeader']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <section id="register" style="max-width:580px;margin:50px auto 0;">
    <div class="form-box">
      <h2>Register Your Interest</h2>
      <p style="color:#666;margin-bottom:22px;font-size:.9rem;">Leave your details and we will contact you about <strong><?= htmlspecialchars($prog['ProgrammeName']) ?></strong>.</p>
      <?php if($success): ?><div class="alert alert-success" role="alert"><?= $success ?></div><?php endif; ?>
      <?php if($error):   ?><div class="alert alert-error"   role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if(!$success): ?>
      <form method="POST" action="programme.php?id=<?= $id ?>#register" novalidate>
        <input type="hidden" name="register" value="1">
        <div class="form-group">
          <label for="fname">Full Name *</label>
          <input type="text" id="fname" name="name" required maxlength="100" placeholder="e.g. Jane Smith" value="<?= isset($_POST['name'])?htmlspecialchars($_POST['name']):'' ?>">
        </div>
        <div class="form-group">
          <label for="femail">Email Address *</label>
          <input type="email" id="femail" name="email" required maxlength="255" placeholder="e.g. jane@email.com" value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):'' ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:4px;">Submit My Interest</button>
        <p style="font-size:.78rem;color:#aaa;text-align:center;margin-top:10px;">Your details are kept private and used only for programme communications.</p>
      </form>
      <?php endif; ?>
    </div>
  </section>

</div>
</main>
<style>@media(max-width:768px){.prog-header{grid-template-columns:1fr!important}}</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
