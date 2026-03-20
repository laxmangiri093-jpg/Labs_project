<?php
session_start();
if (isset($_SESSION['staff_id'])) { header('Location: dashboard.php'); exit; }
require_once _DIR_ . '/../includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT su.*, s.Name AS StaffName, s.JobTitle FROM StaffUsers su
                               JOIN Staff s ON su.StaffID = s.StaffID
                               WHERE su.Username = ? AND su.IsActive = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['PasswordHash'])) {
            $_SESSION['staff_id']     = $user['StaffUserID'];
            $_SESSION['staff_name']   = $user['StaffName'];
            $_SESSION['staff_title']  = $user['JobTitle'];
            $_SESSION['staff_ref']    = $user['StaffID'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Staff Login | Labs Project</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{
      font-family:'DM Sans',sans-serif;
      min-height:100vh;
      background:#0f1923;
      display:flex;
      align-items:center;
      justify-content:center;
      position:relative;
      overflow:hidden;
    }
    body::before{
      content:'';position:absolute;
      width:600px;height:600px;
      background:radial-gradient(circle,rgba(21,101,192,.25) 0%,transparent 70%);
      top:-100px;left:-100px;pointer-events:none;
    }
    body::after{
      content:'';position:absolute;
      width:500px;height:500px;
      background:radial-gradient(circle,rgba(13,71,161,.2) 0%,transparent 70%);
      bottom:-100px;right:-100px;pointer-events:none;
    }
    .grid-bg{
      position:absolute;inset:0;
      background-image:
        linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),
        linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);
      background-size:40px 40px;pointer-events:none;
    }
    .card{
      background:rgba(255,255,255,.04);
      border:1px solid rgba(255,255,255,.1);
      backdrop-filter:blur(20px);
      border-radius:24px;padding:50px 44px;
      width:100%;max-width:440px;
      position:relative;z-index:1;
      box-shadow:0 32px 80px rgba(0,0,0,.4);
      animation:slideUp .5s ease forwards;
    }
    @keyframes slideUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
    .badge{
      display:inline-flex;align-items:center;gap:8px;
      background:rgba(21,101,192,.3);border:1px solid rgba(21,101,192,.5);
      color:#90caf9;font-size:.72rem;font-weight:600;
      padding:5px 14px;border-radius:30px;
      letter-spacing:.8px;text-transform:uppercase;margin-bottom:24px;
    }
    .icon{
      width:64px;height:64px;
      background:linear-gradient(135deg,#1565c0,#1976d2);
      border-radius:16px;display:flex;align-items:center;justify-content:center;
      font-size:1.8rem;margin-bottom:20px;
      box-shadow:0 8px 24px rgba(21,101,192,.4);
    }
    h1{font-family:'DM Serif Display',serif;color:white;font-size:2rem;line-height:1.2;margin-bottom:6px}
    .sub{color:rgba(255,255,255,.45);font-size:.88rem;margin-bottom:34px;line-height:1.5}
    .alert-error{
      padding:12px 16px;border-radius:10px;
      background:rgba(229,57,53,.15);color:#ef9a9a;
      border:1px solid rgba(229,57,53,.3);
      margin-bottom:22px;font-size:.88rem;
    }
    .group{margin-bottom:20px}
    label{display:block;font-size:.78rem;font-weight:600;color:rgba(255,255,255,.5);letter-spacing:.6px;text-transform:uppercase;margin-bottom:8px}
    input{
      width:100%;padding:13px 16px;border-radius:12px;
      border:1.5px solid rgba(255,255,255,.1);
      background:rgba(255,255,255,.06);color:white;
      font-family:'DM Sans',sans-serif;font-size:.92rem;
      outline:none;transition:all .25s;
    }
    input::placeholder{color:rgba(255,255,255,.25)}
    input:focus{border-color:#1976d2;background:rgba(255,255,255,.09);box-shadow:0 0 0 4px rgba(21,101,192,.2)}
    .btn{
      width:100%;padding:14px;border-radius:30px;
      background:linear-gradient(135deg,#1565c0,#1976d2);
      color:white;border:none;font-family:'DM Sans',sans-serif;
      font-size:.95rem;font-weight:700;cursor:pointer;
      transition:all .3s;margin-top:6px;
      box-shadow:0 6px 20px rgba(21,101,192,.4);letter-spacing:.3px;
    }
    .btn:hover{background:linear-gradient(135deg,#0d47a1,#1565c0);transform:translateY(-2px);box-shadow:0 10px 28px rgba(21,101,192,.5)}
    .divider{display:flex;align-items:center;gap:12px;margin:24px 0;color:rgba(255,255,255,.2);font-size:.8rem}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.1)}
    .back{text-align:center;font-size:.83rem;color:rgba(255,255,255,.4)}
    .back a{color:#90caf9;font-weight:600;text-decoration:none}
    .back a:hover{text-decoration:underline}
    .hint{
      margin-top:20px;padding:12px 16px;border-radius:10px;
      background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);
      font-size:.78rem;color:rgba(255,255,255,.35);text-align:center;line-height:1.6;
    }
  </style>
</head>
<body>
<div class="grid-bg"></div>
<div class="card">
  <div class="badge">👤 Staff Portal</div>
  <div class="icon">📋</div>
  <h1>Staff Login</h1>
  <p class="sub">Access your teaching responsibilities and module overview.</p>

  <?php if ($error): ?>
    <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php" novalidate>
    <div class="group">
      <label for="u">Username</label>
      <input type="text" id="u" name="username" required
             placeholder="Enter your username" autocomplete="username"
             value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
    </div>
    <div class="group">
      <label for="p">Password</label>
      <input type="password" id="p" name="password" required
             placeholder="Enter your password" autocomplete="current-password">
    </div>
    <button type="submit" class="btn">Sign In to Staff Portal</button>
  </form>

  <div class="divider">or</div>
  <div class="back"><a href="../index.php">← Back to Student Site</a></div>
  <div class="back" style="margin-top:10px;"><a href="../admin/login.php">Admin Panel →</a></div>
  <div class="hint">Staff accounts are created by the system administrator.<br>Contact admin if you need access.</div>
</div>
</body>
</html>