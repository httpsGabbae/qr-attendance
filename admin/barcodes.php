<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Management - QR Attendance</title>
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
        
        .barcode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .barcode-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .barcode-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .employee-info {
            margin-bottom: 1rem;
        }
        
        .employee-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        .employee-id {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .employee-dept {
            font-size: 0.8rem;
            color: #666;
        }
        
        .barcode-display {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 1rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .barcode-visual {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            line-height: 1;
            color: #000;
            margin: 0.5rem 0;
        }
        
        .card-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
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
            font-size: 0.9rem;
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
        
        .btn.btn-success {
            background: #28a745;
        }
        
        .btn.btn-success:hover {
            background: #218838;
        }
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
        
        .bulk-actions {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .bulk-actions h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .bulk-actions p {
            color: #666;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>🔳 QR Code Management</h1>
            <nav class="header-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="users.php">Users</a>
                <a href="reports.php">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <!-- Bulk Actions -->
        <div class="section">
            <div class="bulk-actions">
                <h3>Bulk Actions</h3>
                <p>Print all QR codes at once or export QR code data</p>
                <button onclick="printAllBarcodes()" class="btn btn-success">🖨️ Print All QR Codes</button>
                <a href="barcode_bulk_print.php" class="btn btn-secondary" target="_blank">📄 Bulk Print Page</a>
            </div>
        </div>
        
        <!-- Individual QR codes -->
        <div class="section">
            <h2>Individual QR Codes (<?php echo count($users); ?>)</h2>
            
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <p>No users found. <a href="users.php">Add users</a> to generate QR codes.</p>
                </div>
            <?php else: ?>
                <div class="barcode-grid">
                    <?php foreach ($users as $user): ?>
                        <div class="barcode-card">
                            <div class="employee-info">
                                <div class="employee-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div class="employee-id">ID: <?php echo htmlspecialchars($user['employee_id']); ?></div>
                                <?php if ($user['department']): ?>
                                    <div class="employee-dept"><?php echo htmlspecialchars($user['department']); ?></div>
                                <?php endif; ?>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo $user['status']; ?>
                                </span>
                            </div>
                            
                            <div class="barcode-display">
                                <div class="barcode-number"><?php echo htmlspecialchars($user['barcode']); ?></div>
                                <img src="../api/qr_image.php?code=<?php echo urlencode($user['barcode']); ?>"
                                     alt="QR code for <?php echo htmlspecialchars($user['name']); ?>"
                                     style="max-width: 100%; background: #fff;">
                                <img src="../api/barcode_image.php?code=<?php echo urlencode($user['barcode']); ?>&scale=2"
                                     alt="Barcode for <?php echo htmlspecialchars($user['name']); ?>"
                                     style="max-width: 100%; background: #fff; margin-top: 6px;">
                            </div>

                            <div class="card-actions">
                                <a href="barcode_print.php?id=<?php echo $user['id']; ?>"
                                   class="btn" target="_blank">🖨️ Print</a>
                                <a href="../api/qr_image.php?code=<?php echo urlencode($user['barcode']); ?>&download=1"
                                   class="btn btn-success">⬇️ QR</a>
                                <a href="../api/barcode_image.php?code=<?php echo urlencode($user['barcode']); ?>&download=1"
                                   class="btn btn-secondary">⬇️ Barcode</a>
                            </div>
                            <div class="card-actions">
                                <button onclick="testBarcode('<?php echo htmlspecialchars($user['barcode'], ENT_QUOTES); ?>')"
                                        class="btn btn-secondary">🧪 Test</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Scanner Instructions -->
        <div class="section">
            <h2>Scanner Setup Instructions</h2>
            <div style="background: #e7f3ff; padding: 1rem; border-radius: 5px;">
                <h3 style="color: #0066cc; margin-bottom: 0.5rem;">Scanner Configuration:</h3>
                <ol style="margin-left: 1.5rem; color: #333;">
                    <li>Use a 2D/QR scanner (or phone camera) pointed at the QR code</li>
                    <li>USB scanners work as keyboard input (HID): scanned text is typed for you</li>
                    <li>Point the scanner at any QR code above to test</li>
                    <li>Make sure the attendance page is open and focused</li>
                </ol>

                <h4 style="color: #0066cc; margin: 1rem 0 0.5rem;">Troubleshooting:</h4>
                <ul style="margin-left: 1.5rem; color: #333;">
                    <li>A 1D-only laser scanner cannot read QR codes — use a 2D imager</li>
                    <li>If scanner doesn't work, try a different USB port</li>
                    <li>Check if the scanner LED lights up when triggered</li>
                    <li>Ensure the QR code is clean and well-printed</li>
                    <li>Test with the QR test function above</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        function printAllBarcodes() {
            if (confirm('This will print all active user QR codes. Continue?')) {
                window.open('barcode_bulk_print.php', '_blank');
            }
        }
        
        function testBarcode(barcode) {
            // Open the main attendance page with the QR code value ready to test
            const url = '../index.php';
            const popup = window.open(url, '_blank', 'width=600,height=400');

            // Wait for popup to load then fill the code
            setTimeout(() => {
                try {
                    if (popup.document) {
                        const input = popup.document.getElementById('barcodeInput');
                        if (input) {
                            input.value = barcode;
                            input.focus();
                            alert('QR code "' + barcode + '" has been entered in the test window. Press Enter or click away to submit.');
                        }
                    }
                } catch (e) {
                    alert('Test QR code: ' + barcode + '\n\nManually enter this in the attendance scanner to test.');
                }
            }, 1000);
        }
    </script>
</body>
</html>