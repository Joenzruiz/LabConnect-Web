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
    <title>My Appointments - SLIMM Laboratory Inc.</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <?php include '../../includes/doctor_sidebar.php'; ?>

    <main class="content">
        <header class="topbar">
            <div class="page-title">
                <h1>My Appointments</h1>
                <p>SLIMM LABORATORY INC. · Baybay City, Leyte</p>
            </div>

            <div class="user-area">
                <div class="avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?></div>
                <a class="btn logout" href="../../api/logout.php">Logout</a>
            </div>
        </header>

        <div class="toolbar">
            <div class="toolbar-left">
                <input class="input search" placeholder="Search my appointments...">
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
                            <th>Date</th>
                            <th>Time</th>
                            <th>Tests</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Juan Dela Cruz</td>
                            <td>2026-09-24</td>
                            <td>09:00 AM</td>
                            <td>CBC</td>
                            <td><span class='badge badge-confirmed'>Confirmed</span></td>
                            <td><button class='btn btn-sm'>Edit</button> <button class='btn btn-sm btn-danger'>Delete</button></td>
                        </tr>
                        <tr>
                            <td>Maria Santos</td>
                            <td>2026-09-25</td>
                            <td>10:30 AM</td>
                            <td>X-Ray</td>
                            <td><span class='badge badge-pending'>Pending</span></td>
                            <td><button class='btn btn-sm'>Edit</button> <button class='btn btn-sm btn-danger'>Delete</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
