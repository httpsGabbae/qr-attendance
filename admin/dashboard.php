<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$summary = getTodayAttendanceSummary();
$recentAttendance = getAttendanceReport(date('Y-m-d'), date('Y-m-d'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QR Attendance</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }
        
        .header {
            background: #667eea;
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .header-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .header-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .header-nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn.btn-secondary {
            background: #6c757d;
        }
        
        .btn.btn-secondary:hover {
            background: #5a6268;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 3px;
        }
        
        .badge.badge-in {
            background: #d4edda;
            color: #155724;
        }
        
        .badge.badge-out {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
        }
        
        .action-card h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .action-card p {
            color: #666;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>📊 Admin Dashboard</h1>
            <nav class="header-nav">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="../index.php">Scanner</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $summary['checked_in'] ?? 0; ?></h3>
                <p>Checked In Today</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $summary['total_scans'] ?? 0; ?></h3>
                <p>Total Scans Today</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $summary['total_users'] ?? 0; ?></h3>
                <p>Total Active Users</p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card">
                <h3>👥 Manage Users</h3>
                <p>Add new users and generate QR codes</p>
                <a href="users.php" class="btn">Manage Users</a>
            </div>
            <div class="action-card">
                <h3>📋 View Reports</h3>
                <p>View detailed attendance reports</p>
                <a href="reports.php" class="btn">View Reports</a>
            </div>
            <div class="action-card">
                <h3>🏷️ Print QR Codes</h3>
                <p>Print user QR codes</p>
                <a href="barcodes.php" class="btn">Print QR Codes</a>
            </div>
        </div>
        
        <!-- Recent Attendance -->
        <div class="section">
            <div class="section-header">
                <h2>Today's Attendance</h2>
                <a href="reports.php" class="btn btn-secondary">View All Reports</a>
            </div>
            
            <?php if (empty($recentAttendance)): ?>
                <div class="empty-state">
                    <p>No attendance records for today yet.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Action</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentAttendance, 0, 10) as $record): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($record['employee_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($record['department'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $record['scan_type']; ?>">
                                        <?php echo strtoupper($record['scan_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('g:i A', strtotime($record['scan_time'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (count($recentAttendance) > 10): ?>
                    <p style="text-align: center; margin-top: 1rem; color: #666;">
                        Showing 10 of <?php echo count($recentAttendance); ?> records
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>