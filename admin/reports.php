<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Handle filters
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$userId = $_GET['user_id'] ?? null;

$users = getAllUsers();
$attendanceRecords = getAttendanceReport($startDate, $endDate, $userId);

// Process data for better display
$processedRecords = [];
foreach ($attendanceRecords as $record) {
    $date = $record['scan_date'];
    $employeeId = $record['employee_id'];
    $key = $date . '_' . $employeeId;
    
    if (!isset($processedRecords[$key])) {
        $processedRecords[$key] = [
            'date' => $date,
            'employee_id' => $employeeId,
            'name' => $record['name'],
            'department' => $record['department'],
            'check_in' => null,
            'check_out' => null
        ];
    }
    
    if ($record['scan_type'] === 'in') {
        if (!$processedRecords[$key]['check_in']) {
            $processedRecords[$key]['check_in'] = $record['scan_time'];
        }
    } else {
        $processedRecords[$key]['check_out'] = $record['scan_time'];
    }
}

// Sort by date and name
usort($processedRecords, function($a, $b) {
    if ($a['date'] === $b['date']) {
        return strcmp($a['name'], $b['name']);
    }
    return strcmp($b['date'], $a['date']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - QR Attendance</title>
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
        
        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 1rem;
        }
        
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #667eea;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
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
            position: sticky;
            top: 0;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
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
        }
        
        .time-cell {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .missing {
            color: #dc3545;
            font-style: italic;
        }
        
        .present {
            color: #28a745;
        }
        
        .filter-bar {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .export-btn {
            background: #28a745;
        }
        
        .export-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>📋 Attendance Reports</h1>
            <nav class="header-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="users.php">Users</a>
                <a href="barcodes.php">QR Codes</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <!-- Statistics -->
        <?php
        $totalPresent = 0;
        $totalAbsent = 0;
        $uniqueDates = array_unique(array_column($processedRecords, 'date'));
        $uniqueUsers = count($users);
        
        foreach ($processedRecords as $record) {
            if ($record['check_in']) {
                $totalPresent++;
            } else {
                $totalAbsent++;
            }
        }
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo count($processedRecords); ?></h3>
                <p>Total Records</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $totalPresent; ?></h3>
                <p>Days Present</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($uniqueDates); ?></h3>
                <p>Date Range</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="section">
            <h2>Filter Reports</h2>
            
            <form method="GET" class="filter-bar">
                <div class="filters">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="user_id">Employee</label>
                        <select id="user_id" name="user_id">
                            <option value="">All Employees</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        <?php echo $userId == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name'] . ' (' . $user['employee_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn">Apply Filters</button>
                <a href="../api/export.php?<?php echo http_build_query($_GET); ?>" class="btn export-btn">📊 Export CSV</a>
            </form>
        </div>
        
        <!-- Reports Table -->
        <div class="section">
            <h2>Attendance Records</h2>
            
            <?php if (empty($processedRecords)): ?>
                <div class="empty-state">
                    <p>No attendance records found for the selected filters.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processedRecords as $record): ?>
                                <?php
                                $hoursWorked = '';
                                $status = 'Absent';
                                
                                if ($record['check_in']) {
                                    $status = 'Present';
                                    if ($record['check_out']) {
                                        $in = new DateTime($record['check_in']);
                                        $out = new DateTime($record['check_out']);
                                        $diff = $out->diff($in);
                                        $hoursWorked = $diff->format('%h:%I');
                                        $status = 'Complete';
                                    } else {
                                        $status = 'Checked In';
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($record['employee_id']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['department'] ?: 'N/A'); ?></td>
                                    <td class="time-cell">
                                        <?php if ($record['check_in']): ?>
                                            <?php echo date('g:i A', strtotime($record['check_in'])); ?>
                                        <?php else: ?>
                                            <span class="missing">Not checked in</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="time-cell">
                                        <?php if ($record['check_out']): ?>
                                            <?php echo date('g:i A', strtotime($record['check_out'])); ?>
                                        <?php elseif ($record['check_in']): ?>
                                            <span class="missing">Not checked out</span>
                                        <?php else: ?>
                                            <span class="missing">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="time-cell">
                                        <?php echo $hoursWorked ?: '-'; ?>
                                    </td>
                                    <td>
                                        <span style="color: <?php 
                                            echo $status === 'Complete' ? '#28a745' : 
                                                ($status === 'Present' || $status === 'Checked In' ? '#ffc107' : '#dc3545'); 
                                        ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>