<?php
session_start();

if (($_SESSION['user_role'] ?? 'doctor') !== 'doctor') {
    header('Location: ../../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Results - SLIMM Laboratory Inc.</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <?php include '../../includes/doctor_sidebar.php'; ?>

    <main class="content">
        <header class="topbar">
            <div class="page-title">
                <h1>Laboratory Results</h1>
                <p>SLIMM LABORATORY INC. · Baybay City, Leyte</p>
            </div>

            <div class="user-area">
                <div class="avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?></div>
                <a class="btn logout" href="../../api/logout.php">Logout</a>
            </div>
        </header>

        <div class="toolbar">
            <div class="toolbar-left">
                <input class="input search" placeholder="Search laboratory results...">
                <select class="select">
                    <option>All statuses</option>
                    <option>Active</option>
                    <option>Inactive</option>
                </select>
            </div>

            <button class="btn btn-primary">+ Add New</button>
        </div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Test</th>
                            <th>Report</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Juan Dela Cruz</td>
                            <td>CBC</td>
                            <td>Pending</td>
                            <td><span class='badge badge-pending'>Awaiting report</span></td>
                            <td><button class='btn btn-sm'>Edit</button> <button class='btn btn-sm btn-danger'>Delete</button></td>
                        </tr>
                        <tr>
                            <td>Maria Santos</td>
                            <td>X-Ray</td>
                            <td>xray_1002.pdf</td>
                            <td><span class='badge badge-completed'>Released</span></td>
                            <td><button class='btn btn-sm'>Edit</button> <button class='btn btn-sm btn-danger'>Delete</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
