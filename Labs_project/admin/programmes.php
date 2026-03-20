<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
$msg=$msgType='';
if(isset($_POST['delete_id'])) { $pdo->prepare("DELETE FROM Programmes WHERE ProgrammeID=?")->execute([(int)$_POST['delete_id']]); $msg='Programme deleted.'; $msgType='success'; }
if(isset($_POST['toggle_id'])) { $pdo->prepare("UPDATE Programmes SET IsPublished=NOT IsPublished WHERE ProgrammeID=?")->execute([(int)$_POST['toggle_id']]); $msg='Visibility updated.'; $msgType='success'; }
if(isset($_POST['save_prog'])) {
  $eid=(int)($_POST['edit_id']??0);
  $name=htmlspecialchars(trim($_POST['pname']??''),ENT_QUOTES,'UTF-8');
  $desc=htmlspecialchars(trim($_POST['pdesc']??''),ENT_QUOTES,'UTF-8');
  $level=(int)($_POST['plevel']??0); $leader=(int)($_POST['pleader']??0);
  if($name&&$level&&$leader) {
    if($eid>0) { $pdo->prepare("UPDATE Programmes SET ProgrammeName=?,Description=?,LevelID=?,ProgrammeLeaderID=? WHERE ProgrammeID=?")->execute([$name,$desc,$level,$leader,$eid]); $msg='Programme updated.'; }
    else { $pdo->prepare("INSERT INTO Programmes(ProgrammeName,Description,LevelID,ProgrammeLeaderID) VALUES(?,?,?,?)")->execute([$name,$desc,$level,$leader]); $msg='Programme added.'; }
    $msgType='success';
  } else { $msg='Please fill in all required fields.'; $msgType='error'; }
}
$programmes=$pdo->query("SELECT p.*,l.LevelName,s.Name AS LeaderName FROM Programmes p JOIN Levels l ON p.LevelID=l.LevelID JOIN Staff s ON p.ProgrammeLeaderID=s.StaffID ORDER BY l.LevelID,p.ProgrammeName")->fetchAll();
$levels=$pdo->query("SELECT * FROM Levels")->fetchAll();
$staff=$pdo->query("SELECT * FROM Staff ORDER BY Name")->fetchAll();
$editing=null;
if(isset($_GET['edit'])) { $es=$pdo->prepare("SELECT * FROM Programmes WHERE ProgrammeID=?"); $es->execute([(int)$_GET['edit']]); $editing=$es->fetch(); }
$pageTitle='Programmes'; $activePage='programmes';
require_once __DIR__ . '/admin_header.php';
?>
<div class="admin-topbar"><h1 class="page-title">Programmes</h1><a href="programmes.php" class="btn btn-primary btn-sm">+ Add New</a></div>
<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="form-box" style="margin-bottom:28px;">
  <h2 style="font-size:1.1rem;"><?= $editing?'Edit Programme':'Add New Programme' ?></h2>
  <form method="POST" action="programmes.php" novalidate>
    <input type="hidden" name="save_prog" value="1">
    <?php if($editing): ?><input type="hidden" name="edit_id" value="<?= $editing['ProgrammeID'] ?>"><?php endif; ?>
    <div class="form-row">
      <div class="form-group">
        <label for="pname">Programme Name *</label>
        <input type="text" id="pname" name="pname" required maxlength="200" placeholder="e.g. BSc Computer Science" value="<?= $editing?htmlspecialchars($editing['ProgrammeName']):'' ?>">
      </div>
      <div class="form-group">
        <label for="plevel">Level *</label>
        <select id="plevel" name="plevel" required>
          <option value="">-- Select Level --</option>
          <?php foreach($levels as $lv): ?>
            <option value="<?= $lv['LevelID'] ?>" <?= ($editing&&$editing['LevelID']==$lv['LevelID'])?'selected':'' ?>><?= htmlspecialchars($lv['LevelName']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label for="pleader">Programme Leader *</label>
      <select id="pleader" name="pleader" required>
        <option value="">-- Select Staff --</option>
        <?php foreach($staff as $st): ?>
          <option value="<?= $st['StaffID'] ?>" <?= ($editing&&$editing['ProgrammeLeaderID']==$st['StaffID'])?'selected':'' ?>><?= htmlspecialchars($st['Name']) ?><?= $st['JobTitle']?' — '.htmlspecialchars($st['JobTitle']):'' ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="pdesc">Description</label>
      <textarea id="pdesc" name="pdesc" rows="3" placeholder="Describe this programme..."><?= $editing?htmlspecialchars($editing['Description']):'' ?></textarea>
    </div>
    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary"><?= $editing?'Save Changes':'Add Programme' ?></button>
      <?php if($editing): ?><a href="programmes.php" class="btn btn-outline">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>Programme</th><th>Level</th><th>Leader</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
    <?php if(empty($programmes)): ?><tr><td colspan="5" style="text-align:center;padding:30px;color:#666;">No programmes yet.</td></tr>
    <?php else: foreach($programmes as $p): ?>
      <tr>
        <td><strong><?= htmlspecialchars($p['ProgrammeName']) ?></strong></td>
        <td><span class="badge <?= $p['LevelID']==1?'badge-ug':'badge-pg' ?>"><?= htmlspecialchars($p['LevelName']) ?></span></td>
        <td><?= htmlspecialchars($p['LeaderName']) ?></td>
        <td style="font-weight:600;color:<?= $p['IsPublished']?'#2e7d32':'#e65100' ?>;"><?= $p['IsPublished']?'✔ Published':'✎ Draft' ?></td>
        <td style="white-space:nowrap;">
          <a href="programmes.php?edit=<?= $p['ProgrammeID'] ?>" class="btn btn-outline btn-sm">Edit</a>
          <form method="POST" style="display:inline;"><input type="hidden" name="toggle_id" value="<?= $p['ProgrammeID'] ?>"><button type="submit" class="btn btn-warning btn-sm"><?= $p['IsPublished']?'Unpublish':'Publish' ?></button></form>
          <a href="students.php?prog=<?= $p['ProgrammeID'] ?>" class="btn btn-outline btn-sm">Students</a>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this programme?')"><input type="hidden" name="delete_id" value="<?= $p['ProgrammeID'] ?>"><button type="submit" class="btn btn-danger btn-sm">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table></div>
  </main></div></body></html>
