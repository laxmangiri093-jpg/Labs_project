<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

$msg = $msgType = '';

// Handle adding new staff account
if (isset($_POST['add_staff'])) {
    $staff_id = (int)($_POST['staff_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$staff_id || !$username || !$password) {
        $msg = 'Please fill in all required fields.';
        $msgType = 'error';
    } elseif (strlen($password) < 6) {
        $msg = 'Password must be at least 6 characters.';
        $msgType = 'error';
    } else {
        // Check if username already exists
        $check = $pdo->prepare("SELECT StaffUserID FROM StaffUsers WHERE Username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $msg = 'Username already exists.';
            $msgType = 'error';
        } else {
            // Check if staff already has an account
            $checkStaff = $pdo->prepare("SELECT StaffUserID FROM StaffUsers WHERE StaffID = ?");
            $checkStaff->execute([$staff_id]);
            if ($checkStaff->fetch()) {
                $msg = 'This staff member already has an account.';
                $msgType = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $insert = $pdo->prepare("INSERT INTO StaffUsers (StaffID, Username, PasswordHash, IsActive) VALUES (?, ?, ?, 1)");
                $insert->execute([$staff_id, $username, $hash]);
                $msg = 'Staff account created successfully.';
                $msgType = 'success';
            }
        }
    }
}

// Handle password reset
if (isset($_POST['reset_password'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    
    if (!$user_id || !$new_password) {
        $msg = 'Please enter a new password.';
        $msgType = 'error';
    } elseif (strlen($new_password) < 6) {
        $msg = 'Password must be at least 6 characters.';
        $msgType = 'error';
    } else {
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE StaffUsers SET PasswordHash = ? WHERE StaffUserID = ?");
        $update->execute([$hash, $user_id]);
        $msg = 'Password reset successfully.';
        $msgType = 'success';
    }
}

// Handle toggle account status (activate/deactivate)
if (isset($_POST['toggle_status'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $current = $pdo->prepare("SELECT IsActive FROM StaffUsers WHERE StaffUserID = ?");
    $current->execute([$user_id]);
    $status = $current->fetchColumn();
    $new_status = $status ? 0 : 1;
    $update = $pdo->prepare("UPDATE StaffUsers SET IsActive = ? WHERE StaffUserID = ?");
    $update->execute([$new_status, $user_id]);
    $msg = $new_status ? 'Account activated.' : 'Account deactivated.';
    $msgType = 'success';
}

// Handle delete account
if (isset($_POST['delete_account'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $delete = $pdo->prepare("DELETE FROM StaffUsers WHERE StaffUserID = ?");
    $delete->execute([$user_id]);
    $msg = 'Staff account deleted.';
    $msgType = 'success';
}

// Get all staff accounts with their details
$staffAccounts = $pdo->query("
    SELECT su.StaffUserID, su.Username, su.IsActive, su.CreatedAt,
           s.StaffID, s.Name, s.JobTitle
    FROM StaffUsers su
    JOIN Staff s ON su.StaffID = s.StaffID
    ORDER BY s.Name
")->fetchAll();

// Get staff members who don't have accounts yet (for adding new accounts)
$availableStaff = $pdo->query("
    SELECT s.StaffID, s.Name, s.JobTitle
    FROM Staff s
    LEFT JOIN StaffUsers su ON s.StaffID = su.StaffID
    WHERE su.StaffUserID IS NULL
    ORDER BY s.Name
")->fetchAll();

$pageTitle = 'Staff Accounts';
$activePage = 'staff_accounts';
require_once __DIR__ . '/admin_header.php';
?>

<div class="admin-topbar">
    <h1 class="page-title">Staff Accounts</h1>
    <button onclick="document.getElementById('addForm').scrollIntoView({behavior:'smooth'})" class="btn btn-primary btn-sm">+ Add New Account</button>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- Add New Staff Account Form -->
<div class="form-box" id="addForm" style="margin-bottom: 28px;">
    <h2 style="font-size: 1.1rem;">➕ Add New Staff Account</h2>
    <form method="POST" action="staff_accounts.php" novalidate>
        <input type="hidden" name="add_staff" value="1">
        <div class="form-row">
            <div class="form-group">
                <label for="staff_id">Staff Member *</label>
                <select id="staff_id" name="staff_id" required>
                    <option value="">-- Select Staff Member --</option>
                    <?php foreach ($availableStaff as $staff): ?>
                        <option value="<?= $staff['StaffID'] ?>">
                            <?= htmlspecialchars($staff['Name']) ?> 
                            <?= $staff['JobTitle'] ? '— ' . htmlspecialchars($staff['JobTitle']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($availableStaff)): ?>
                    <small style="color: #e65100;">All staff members already have accounts.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required 
                       placeholder="e.g., dr.johnson" maxlength="100">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="text" id="password" name="password" required 
                       placeholder="Minimum 6 characters">
                <small style="color: #666;">Default password format: Staff2024 (can be changed later)</small>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
            </div>
        </div>
    </form>
</div>

<!-- Existing Staff Accounts Table -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Staff Name</th>
                <th>Job Title</th>
                <th>Username</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($staffAccounts)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:30px; color:#666;">
                        No staff accounts created yet.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($staffAccounts as $account): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($account['Name']) ?></td>
                        <td><?= htmlspecialchars($account['JobTitle'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($account['Username']) ?></td>
                        <td>
                            <span class="badge <?= $account['IsActive'] ? 'badge-ug' : 'badge-pg' ?>" 
                                  style="<?= !$account['IsActive'] ? 'background:#fff3e0;color:#e65100;' : '' ?>">
                                <?= $account['IsActive'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td style="font-size:.82rem;"><?= date('d M Y', strtotime($account['CreatedAt'])) ?></td>
                        <td style="white-space:nowrap;">
                            <!-- Reset Password Form -->
                            <form method="POST" style="display:inline-block; margin-right:4px;" 
                                  onsubmit="return confirm('Reset password for <?= htmlspecialchars($account['Name']) ?>?')">
                                <input type="hidden" name="user_id" value="<?= $account['StaffUserID'] ?>">
                                <input type="hidden" name="new_password" value="Staff2024">
                                <button type="submit" name="reset_password" class="btn btn-outline btn-sm" 
                                        title="Reset to default password (Staff2024)">
                                    🔑 Reset
                                </button>
                            </form>
                            
                            <!-- Custom Password Reset (with prompt) -->
                            <form method="POST" style="display:inline-block; margin-right:4px;" 
                                  onsubmit="event.preventDefault(); customReset(<?= $account['StaffUserID'] ?>, '<?= htmlspecialchars($account['Name']) ?>')">
                                <button type="button" class="btn btn-warning btn-sm" 
                                        onclick="customReset(<?= $account['StaffUserID'] ?>, '<?= htmlspecialchars($account['Name']) ?>')">
                                    🔒 Set Password
                                </button>
                            </form>
                            
                            <!-- Toggle Status -->
                            <form method="POST" style="display:inline-block; margin-right:4px;">
                                <input type="hidden" name="user_id" value="<?= $account['StaffUserID'] ?>">
                                <button type="submit" name="toggle_status" class="btn btn-<?= $account['IsActive'] ? 'warning' : 'success' ?> btn-sm">
                                    <?= $account['IsActive'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                            
                            <!-- Delete Account -->
                            <form method="POST" style="display:inline-block;" 
                                  onsubmit="return confirm('Delete account for <?= htmlspecialchars($account['Name']) ?>? This cannot be undone.')">
                                <input type="hidden" name="user_id" value="<?= $account['StaffUserID'] ?>">
                                <button type="submit" name="delete_account" class="btn btn-danger btn-sm">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Custom Password Reset JavaScript -->
<script>
function customReset(userId, staffName) {
    let newPassword = prompt(`Enter new password for ${staffName}:\n(Minimum 6 characters)`, "Staff2024");
    if (newPassword && newPassword.length >= 6) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = 'staff_accounts.php';
        let inputUserId = document.createElement('input');
        inputUserId.type = 'hidden';
        inputUserId.name = 'user_id';
        inputUserId.value = userId;
        let inputPassword = document.createElement('input');
        inputPassword.type = 'hidden';
        inputPassword.name = 'new_password';
        inputPassword.value = newPassword;
        let inputReset = document.createElement('input');
        inputReset.type = 'hidden';
        inputReset.name = 'reset_password';
        inputReset.value = '1';
        form.appendChild(inputUserId);
        form.appendChild(inputPassword);
        form.appendChild(inputReset);
        document.body.appendChild(form);
        form.submit();
    } else if (newPassword) {
        alert('Password must be at least 6 characters long.');
    }
}
</script>

<style>
.btn-success {
    background: #2e7d32;
    color: white;
}
.btn-success:hover {
    background: #1b5e20;
}
</style>

</main></div></body>
</html>