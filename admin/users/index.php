<?php
session_start();
require_once '../../config/database.php';
if (($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: ../../index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'add') {
        $email = strtolower(trim($_POST['email']));
        $hash  = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role  = $_POST['role'];
        $pdo->prepare("INSERT INTO users (email,password_hash,role,status) VALUES (?,?,?,'Active')")
            ->execute([$email, $hash, $role]);

        // If Doctor → auto-create or link a doctors profile row
        if ($role === 'Doctor') {
            $newUserId = $pdo->lastInsertId();
            if (!$newUserId) {
                $newUserId = $pdo->query("SELECT user_id FROM users WHERE email = '$email' LIMIT 1")->fetchColumn();
            }
            
            // Check if doctor profile already exists with this email
            $existingDoc = $pdo->prepare("SELECT doctor_id FROM doctors WHERE email = ?");
            $existingDoc->execute([$email]);
            $docId = $existingDoc->fetchColumn();
            
            if ($docId) {
                // Link existing doctor profile to the new user account
                $pdo->prepare("UPDATE doctors SET user_id = ? WHERE doctor_id = ?")
                    ->execute([$newUserId, $docId]);
            } else {
                // Extract a name from email (before @) as placeholder
                $namePart = explode('@', $email)[0];
                $pdo->prepare("INSERT INTO doctors (user_id, first_name, last_name, email, specialization) VALUES (?, ?, '', ?, 'General Practitioner')")
                    ->execute([$newUserId, ucfirst($namePart), $email]);
            }
        }
    } elseif ($act === 'toggle') {
        $curr = $pdo->prepare("SELECT status FROM users WHERE user_id=?");
        $curr->execute([$_POST['user_id']]);
        $curr = $curr->fetchColumn();
        $new = $curr === 'Active' ? 'Disabled' : 'Active';
        $pdo->prepare("UPDATE users SET status=? WHERE user_id=?")->execute([$new,$_POST['user_id']]);
    } elseif ($act === 'delete') {
        $pdo->prepare("DELETE FROM users WHERE user_id=?")->execute([$_POST['user_id']]);
    }
    header('Location: index.php'); exit;
}

$users = $pdo->query("SELECT * FROM users WHERE role != 'Patient' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Users - SLIMM Lab</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Users</h1><p>Manage admin, staff, and doctor accounts.</p></div>
        <button class="btn btn-primary" onclick="document.getElementById('dlg-add').showModal()">+ Add User</button>
    </header>

    <div class="page-body">
    <div class="panel">
        <div class="table-card table-scroll">
        <table>
            <thead><tr><th>#</th><th>Email</th><th>Role</th><th>Status</th><th>Last Active</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['user_id'] ?></td>
                <td><strong><?= htmlspecialchars($u['email']) ?></strong></td>
                <td><?= $u['role'] ?></td>
                <td><span class="badge <?= $u['status']==='Active' ? 'badge-confirmed' : 'badge-cancelled' ?>"><?= $u['status'] ?></span></td>
                <td><?= $u['last_active'] ? date('M d, Y H:i', strtotime($u['last_active'])) : '—' ?></td>
                <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                <td class="actions">
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="_action" value="toggle">
                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <button class="btn btn-sm"><?= $u['status']==='Active' ? 'Disable' : 'Enable' ?></button>
                    </form>
                    <?php if ($u['user_id'] != ($_SESSION['user_id'] ?? 0)): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user?')">
                        <input type="hidden" name="_action" value="delete">
                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    </div>
</main>

<dialog id="dlg-add">
<form method="POST">
<input type="hidden" name="_action" value="add">
<h3 style="margin:0 0 18px">Add User</h3>
<div class="field"><label>Email *</label><input class="input" type="email" name="email" required></div>
<div class="field"><label>Password *</label><input class="input" type="password" name="password" required minlength="6"></div>
<div class="field"><label>Role *</label>
    <select class="input" name="role" required>
        <option>Administrator</option>
        <option>Laboratory Staff</option>
        <option>Doctor</option>
    </select>
</div>
<div class="form-actions">
    <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
    <button type="submit" class="btn btn-primary">Create User</button>
</div>
</form>
</dialog>
</body></html>