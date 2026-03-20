<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
$msg=$msgType='';
if(isset($_POST['delete_id'])) {
  try { $pdo->prepare("DELETE FROM Modules WHERE ModuleID=?")->execute([(int)$_POST['delete_id']]); $msg='Module deleted.'; $msgType='success'; }
  catch(PDOException $e) { $msg='Cannot delete: module is linked to programmes.'; $msgType='error'; }
}
if(isset($_POST['save_module'])) {
  $eid=(int)($_POST['edit_id']??0);
  $name=htmlspecialchars(trim($_POST['mname']??''),ENT_QUOTES,'UTF-8');
  $desc=htmlspecialchars(trim($_POST['mdesc']??''),ENT_QUOTES,'UTF-8');
  $leader=(int)($_POST['mleader']??0);
  if($name&&$leader) {
    if($eid>0) { $pdo->prepare("UPDATE Modules SET ModuleName=?,Description=?,ModuleLeaderID=? WHERE ModuleID=?")->execute([$name,$desc,$leader,$eid]); $msg='Module updated.'; }
    else { $maxID=(int)$pdo->query("SELECT MAX(ModuleID) FROM Modules")->fetchColumn(); $pdo->prepare("INSERT INTO Modules(ModuleID,ModuleName,Description,ModuleLeaderID) VALUES(?,?,?,?)")->execute([$maxID+1,$name,$desc,$leader]); $msg='Module added.'; }
    $msgType='success';
  } else { $msg='Please fill in all required fields.'; $msgType='error'; }
}
$modules=$pdo->query("SELECT m.*,s.Name AS LeaderName FROM Modules m JOIN Staff s ON m.ModuleLeaderID=s.StaffID ORDER BY m.ModuleName")->fetchAll();
$staff=$pdo->query("SELECT * FROM Staff ORDER BY Name")->fetchAll();
$editing=null;
if(isset($_GET['edit'])) { $es=$pdo->prepare("SELECT * FROM Modules WHERE ModuleID=?"); $es->execute([(int)$_GET['edit']]); $editing=$es->fetch(); }
$pageTitle='Modules'; $activePage='modules';
require_once __DIR__ . '/admin_header.php';
?>
<div class="admin-topbar"><h1 class="page-title">Modules</h1><a href="modules.php" class="btn btn-primary btn-sm">+ Add New</a></div>
<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="form-box" style="margin-bottom:28px;">
  <h2 style="font-size:1.1rem;"><?= $editing?'Edit Module':'Add New Module' ?></h2>
  <form method="POST" action="modules.php" novalidate>
    <input type="hidden" name="save_module" value="1">
    <?php if($editing): ?><input type="hidden" name="edit_id" value="<?= $editing['ModuleID'] ?>"><?php endif; ?>
    <div class="form-row">
      <div class="form-group">
        <label for="mname">Module Name *</label>
        <input type="text" id="mname" name="mname" required maxlength="200" placeholder="e.g. Introduction to Programming" value="<?= $editing?htmlspecialchars($editing['ModuleName']):'' ?>">
      </div>
      <div class="form-group">
        <label for="mleader">Module Leader *</label>
        <select id="mleader" name="mleader" required>
          <option value="">-- Select Staff --</option>
          <?php foreach($staff as $st): ?><option value="<?= $st['StaffID'] ?>" <?= ($editing&&$editing['ModuleLeaderID']==$st['StaffID'])?'selected':'' ?>><?= htmlspecialchars($st['Name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label for="mdesc">Description</label>
      <textarea id="mdesc" name="mdesc" rows="3" placeholder="Describe this module..."><?= $editing?htmlspecialchars($editing['Description']):'' ?></textarea>
    </div>
    <div style="display:flex;gap:10px;">
      <button type="submit" class="btn btn-primary"><?= $editing?'Save Changes':'Add Module' ?></button>
      <?php if($editing): ?><a href="modules.php" class="btn btn-outline">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>Module Name</th><th>Leader</th><th>Actions</th></tr></thead>
  <tbody>
    <?php if(empty($modules)): ?><tr><td colspan="3" style="text-align:center;padding:30px;color:#666;">No modules.</td></tr>
    <?php else: foreach($modules as $m): ?>
      <tr>
        <td><strong><?= htmlspecialchars($m['ModuleName']) ?></strong><br><small style="color:#888;"><?= htmlspecialchars(mb_substr($m['Description']??'',0,80)) ?>...</small></td>
        <td><?= htmlspecialchars($m['LeaderName']) ?></td>
        <td style="white-space:nowrap;">
          <a href="modules.php?edit=<?= $m['ModuleID'] ?>" class="btn btn-outline btn-sm">Edit</a>
          <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this module?')"><input type="hidden" name="delete_id" value="<?= $m['ModuleID'] ?>"><button type="submit" class="btn btn-danger btn-sm">Delete</button></form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table></div>
  </main></div></body></html>
