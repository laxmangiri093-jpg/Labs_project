<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

$totalProg     = $pdo->query("SELECT COUNT(*) FROM Programmes")->fetchColumn();
$publishedProg = $pdo->query("SELECT COUNT(*) FROM Programmes WHERE IsPublished=1")->fetchColumn();
$totalModules  = $pdo->query("SELECT COUNT(*) FROM Modules")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM InterestedStudents WHERE IsActive=1")->fetchColumn();
$totalStaff    = $pdo->query("SELECT COUNT(*) FROM Staff")->fetchColumn();

// All interested students with full details
$students = $pdo->query("
    SELECT i.InterestID, i.StudentName, i.Email, i.RegisteredAt,
           p.ProgrammeName, p.ProgrammeID, l.LevelName, l.LevelID
    FROM InterestedStudents i
    JOIN Programmes p ON i.ProgrammeID = p.ProgrammeID
    JOIN Levels l ON p.LevelID = l.LevelID
    WHERE i.IsActive = 1
    ORDER BY i.RegisteredAt DESC
")->fetchAll();

$allProg = $pdo->query("SELECT ProgrammeID, ProgrammeName FROM Programmes ORDER BY ProgrammeName")->fetchAll();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/admin_header.php';
?>

<div class="admin-topbar">
  <h1 class="page-title">Dashboard</h1>
  <span style="color:#666;font-size:.9rem;">Welcome, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></span>
</div>

<!-- STAT CARDS -->
<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= $totalProg ?></div><div class="lbl">Total Programmes</div></div>
  <div class="stat-card"><div class="num"><?= $publishedProg ?></div><div class="lbl">Published</div></div>
  <div class="stat-card"><div class="num"><?= $totalProg - $publishedProg ?></div><div class="lbl">Drafts</div></div>
  <div class="stat-card"><div class="num"><?= $totalModules ?></div><div class="lbl">Modules</div></div>
  <div class="stat-card"><div class="num"><?= $totalStudents ?></div><div class="lbl">Registered Students</div></div>
  <div class="stat-card"><div class="num"><?= $totalStaff ?></div><div class="lbl">Staff Members</div></div>
</div>

<!-- INTERESTED STUDENTS PANEL -->
<div class="form-box">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px;">
    <h2 style="font-size:1.1rem;margin:0;padding:0;border:none;">
      Interested Students
      <span style="font-size:.78rem;font-weight:500;color:#888;margin-left:8px;">(<?= count($students) ?> total)</span>
    </h2>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <select id="prog-filter" onchange="filterTable()"
              style="padding:7px 12px;border-radius:8px;border:1.5px solid #dde3f0;font-family:'Segoe UI',sans-serif;font-size:.83rem;background:#f4f7fb;cursor:pointer;outline:none;">
        <option value="">All Programmes</option>
        <?php foreach ($allProg as $ap): ?>
          <option value="prog-<?= $ap['ProgrammeID'] ?>"><?= htmlspecialchars($ap['ProgrammeName']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="search" id="stu-search" placeholder="Search name or email…" oninput="filterTable()"
             style="padding:7px 14px;border-radius:8px;border:1.5px solid #dde3f0;font-family:'Segoe UI',sans-serif;font-size:.83rem;background:#f4f7fb;outline:none;width:200px;">
      <a href="students.php?export=1" class="btn btn-outline btn-sm">⬇ Export CSV</a>
    </div>
  </div>

  <?php if (empty($students)): ?>
    <p style="color:#666;text-align:center;padding:30px 0;">No students have registered interest yet.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table id="stu-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Programme</th>
          <th>Level</th>
          <th>Registered</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php $n = 1; foreach ($students as $s): ?>
          <tr class="stu-row prog-<?= $s['ProgrammeID'] ?>"
              data-name="<?= strtolower(htmlspecialchars($s['StudentName'])) ?>"
              data-email="<?= strtolower(htmlspecialchars($s['Email'])) ?>">
            <td class="row-num" style="color:#aaa;font-size:.82rem;"><?= $n++ ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($s['StudentName']) ?></td>
            <td>
              <a href="mailto:<?= htmlspecialchars($s['Email']) ?>"
                 style="color:#1565c0;text-decoration:none;font-size:.87rem;">
                <?= htmlspecialchars($s['Email']) ?>
              </a>
            </td>
            <td style="font-size:.87rem;"><?= htmlspecialchars($s['ProgrammeName']) ?></td>
            <td>
              <span class="badge <?= $s['LevelID'] == 1 ? 'badge-ug' : 'badge-pg' ?>">
                <?= $s['LevelName'] === 'Undergraduate' ? 'UG' : 'PG' ?>
              </span>
            </td>
            <td style="color:#888;font-size:.82rem;white-space:nowrap;"><?= date('d M Y', strtotime($s['RegisteredAt'])) ?></td>
            <td>
              <form method="POST" action="students.php" style="display:inline;"
                    onsubmit="return confirm('Remove this student?')">
                <input type="hidden" name="remove_id" value="<?= $s['InterestID'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;flex-wrap:wrap;gap:8px;">
    <p id="filter-count" style="color:#888;font-size:.82rem;">
      Showing all <?= count($students) ?> registration<?= count($students) !== 1 ? 's' : '' ?>
    </p>
    <a href="students.php" class="btn btn-outline btn-sm">Manage All Students →</a>
  </div>
  <?php endif; ?>
</div>

<script>
function filterTable() {
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
    `Showing ${visible} registration${visible !== 1 ? 's' : ''}`;
}
</script>

  </main></div></body></html>
