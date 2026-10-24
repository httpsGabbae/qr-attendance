<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submission
if (($_POST['action'] ?? '') === 'add_user') {
    $employeeId = trim($_POST['employee_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    
    if (empty($employeeId) || empty($name)) {
        $message = 'Employee ID and Name are required';
        $messageType = 'error';
    } else {
        $result = addUser($employeeId, $name, $email, $department, $position);
        
        if ($result['success']) {
            $message = "User added successfully! QR code: {$result['barcode']}";
            $messageType = 'success';
            $newBarcode = $result['barcode'];
            $newUserId = $result['user_id'];
        } else {
            $message = $result['error'] ?? 'Failed to add user';
            $messageType = 'error';
        }
    }
}

$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - QR Attendance</title>
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
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        
        .btn.btn-small {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
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
        
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .barcode {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
        }
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>👥 User Management</h1>
            <nav class="header-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="reports.php">Reports</a>
                <a href="barcodes.php">QR Codes</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($newBarcode)): ?>
            <div class="section" style="border: 2px solid #28a745;">
                <h2>✅ New QR Code Ready</h2>
                <p>Scan-test this with your MRBoss POS R45 on the <a href="../index.php">scanner page</a>.</p>
                <div style="text-align: center; margin: 1rem 0;">
                    <img src="../api/qr_image.php?code=<?php echo urlencode($newBarcode); ?>"
                         alt="QR code <?php echo htmlspecialchars($newBarcode); ?>"
                         style="max-width: 100%; border: 1px solid #ddd; padding: 10px; background: #fff;">
                    <br>
                    <img src="../api/barcode_image.php?code=<?php echo urlencode($newBarcode); ?>&scale=3"
                         alt="Barcode <?php echo htmlspecialchars($newBarcode); ?>"
                         style="max-width: 100%; border: 1px solid #ddd; padding: 10px; background: #fff; margin-top: 8px;">
                    <div style="font-family: 'Courier New', monospace; font-weight: bold; margin-top: 0.5rem;">
                        <?php echo htmlspecialchars($newBarcode); ?>
                    </div>
                </div>
                <div>
                    <a class="btn" style="background: #28a745;"
                       href="../api/qr_image.php?code=<?php echo urlencode($newBarcode); ?>&download=1">⬇️ QR PNG</a>
                    <a class="btn" style="background: #28a745;"
                       href="../api/barcode_image.php?code=<?php echo urlencode($newBarcode); ?>&download=1">⬇️ Barcode PNG</a>
                    <a class="btn btn-secondary" target="_blank"
                       href="barcode_print.php?id=<?php echo (int)$newUserId; ?>">🖨️ Print</a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Add User Form -->
        <div class="section">
            <h2>Add New User</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="employee_id">Employee ID *</label>
                        <input type="text" id="employee_id" name="employee_id" required 
                               value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" 
                               value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" 
                               value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn">Add User & Generate QR Code</button>
            </form>
        </div>
        
        <!-- Users List -->
        <div class="section">
            <h2>All Users (<?php echo count($users); ?>)</h2>
            
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <p>No users found. Add your first user above.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>QR Code</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['employee_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['department'] ?: 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['position'] ?: 'N/A'); ?></td>
                                <td>
                                    <img src="../api/qr_image.php?code=<?php echo urlencode($user['barcode']); ?>&scale=2"
                                         alt="QR code" style="height: 60px; background: #fff;">
                                    <br>
                                    <span class="barcode"><?php echo htmlspecialchars($user['barcode']); ?></span>
                                </td>
                                <td>
                                    <span style="color: <?php echo $user['status'] === 'active' ? 'green' : 'red'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="barcode_print.php?id=<?php echo $user['id']; ?>"
                                       class="btn btn-small btn-secondary" target="_blank">Print</a>
                                    <a href="../api/qr_image.php?code=<?php echo urlencode($user['barcode']); ?>&download=1"
                                       class="btn btn-small" style="margin-top: 4px;">PNG</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>