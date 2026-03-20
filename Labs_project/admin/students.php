<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';
$msg=$msgType='';
if(isset($_POST['remove_id'])) { $pdo->prepare("UPDATE InterestedStudents SET IsActive=0 WHERE InterestID=?")->execute([(int)$_POST['remove_id']]); $msg='Student removed from mailing list.'; $msgType='success'; }
if(isset($_GET['export'])) {
  $pf=isset($_GET['prog'])?(int)$_GET['prog']:null;
  $sql="SELECT i.StudentName,i.Email,p.ProgrammeName,l.LevelName,i.RegisteredAt FROM InterestedStudents i JOIN Programmes p ON i.ProgrammeID=p.ProgrammeID JOIN Levels l ON p.LevelID=l.LevelID WHERE i.IsActive=1";
  $params=[];
  if($pf){$sql.=" AND i.ProgrammeID=?";$params[]=$pf;}
  $sql.=" ORDER BY p.ProgrammeName,i.StudentName";
  $rows=$pdo->prepare($sql); $rows->execute($params);
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="mailing-list-'.date('Y-m-d').'.csv"');
  $out=fopen('php://output','w');
  fputcsv($out,['Name','Email','Programme','Level','Registered']);
  foreach($rows->fetchAll() as $r) fputcsv($out,[$r['StudentName'],$r['Email'],$r['ProgrammeName'],$r['LevelName'],date('d/m/Y',strtotime($r['RegisteredAt']))]);
  fclose($out); exit;
}
$pf=isset($_GET['prog'])?(int)$_GET['prog']:null;
$sql="SELECT i.*,p.ProgrammeName,l.LevelName FROM InterestedStudents i JOIN Programmes p ON i.ProgrammeID=p.ProgrammeID JOIN Levels l ON p.LevelID=l.LevelID WHERE i.IsActive=1";
$params=[];
if($pf){$sql.=" AND i.ProgrammeID=?";$params[]=$pf;}
$sql.=" ORDER BY p.ProgrammeName,i.StudentName";
$stmt=$pdo->prepare($sql); $stmt->execute($params);
$students=$stmt->fetchAll();
$allProg=$pdo->query("SELECT ProgrammeID,ProgrammeName FROM Programmes ORDER BY ProgrammeName")->fetchAll();
$filteredName='';
if($pf) foreach($allProg as $ap) if($ap['ProgrammeID']==$pf) $filteredName=$ap['ProgrammeName'];
$pageTitle='Students'; $activePage='students';
require_once __DIR__ . '/admin_header.php';
?>
<div class="admin-topbar">
  <h1 class="page-title">Students <?= $filteredName?'<span style="font-size:1rem;color:#666;font-weight:400;"> — '.htmlspecialchars($filteredName).'</span>':'' ?></h1>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a href="students.php?export=1<?= $pf?'&prog='.$pf:'' ?>" class="btn btn-primary btn-sm">⬇ Export CSV</a>
    <?php if($pf): ?><a href="students.php" class="btn btn-outline btn-sm">Show All</a><?php endif; ?>
  </div>
</div>
<?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<div class="form-box" style="margin-bottom:22px;padding:18px 22px;">
  <form method="GET" action="students.php" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
    <div class="form-group" style="margin:0;flex:1;min-width:200px;">
      <label for="pf">Filter by Programme</label>
      <select id="pf" name="prog">
        <option value="">All Programmes</option>
        <?php foreach($allProg as $ap): ?><option value="<?= $ap['ProgrammeID'] ?>" <?= $pf==$ap['ProgrammeID']?'selected':'' ?>><?= htmlspecialchars($ap['ProgrammeName']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm" style="height:44px;">Filter</button>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Programme</th><th>Level</th><th>Registered</th><th>Action</th></tr></thead>
  <tbody>
    <?php if(empty($students)): ?><tr><td colspan="7" style="text-align:center;padding:30px;color:#666;">No students registered.</td></tr>
    <?php else: foreach($students as $i=>$s): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($s['StudentName']) ?></td>
        <td><a href="mailto:<?= htmlspecialchars($s['Email']) ?>"><?= htmlspecialchars($s['Email']) ?></a></td>
        <td><a href="students.php?prog=<?= $s['ProgrammeID'] ?>"><?= htmlspecialchars($s['ProgrammeName']) ?></a></td>
        <td><span class="badge <?= strpos($s['LevelName'],'Under')!==false?'badge-ug':'badge-pg' ?>"><?= htmlspecialchars($s['LevelName']) ?></span></td>
        <td><?= date('d M Y',strtotime($s['RegisteredAt'])) ?></td>
        <td>
          <form method="POST" action="students.php<?= $pf?'?prog='.$pf:'' ?>" style="display:inline;" onsubmit="return confirm('Remove this student?')">
            <input type="hidden" name="remove_id" value="<?= $s['InterestID'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
          </form>
        </td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table></div>
<p style="color:#666;font-size:.85rem;margin-top:12px;">Total: <strong><?= count($students) ?></strong> registration<?= count($students)!==1?'s':'' ?></p>
  </main></div></body></html>
