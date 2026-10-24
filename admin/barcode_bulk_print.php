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
    <title>Bulk Print QR Codes - QR Attendance</title>
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print-area, .print-area * {
                visibility: visible;
            }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none;
            }
            .page-break {
                page-break-after: always;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .controls {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
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
        
        .print-area {
            max-width: 8.5in;
            margin: 0 auto;
            background: white;
        }
        
        .barcode-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 20px;
        }
        
        .barcode-card {
            border: 2px solid #333;
            border-radius: 10px;
            padding: 20px;
            background: white;
            text-align: center;
            break-inside: avoid;
        }
        
        .company-header {
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .company-subtitle {
            font-size: 0.8rem;
            color: #666;
        }
        
        .employee-info {
            margin: 15px 0;
        }
        
        .employee-name {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }
        
        .employee-id {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 2px;
        }
        
        .employee-dept {
            font-size: 0.8rem;
            color: #666;
        }
        
        .barcode-display {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        
        .barcode-visual {
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            line-height: 1;
            color: #000;
            margin: 8px 0;
        }
        
        .instructions {
            margin-top: 10px;
            font-size: 0.7rem;
            color: #666;
            line-height: 1.3;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .empty-state {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <h2>Bulk QR Code Print</h2>
        <p>This page contains all active user QR codes ready for printing.</p>
        <button onclick="window.print()" class="btn">🖨️ Print All QR Codes</button>
        <a href="barcodes.php" class="btn btn-secondary">← Back to Barcode Management</a>
    </div>
    
    <div class="print-area">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <p>No users found. Add users first to generate barcodes.</p>
            </div>
        <?php else: ?>
            <div class="barcode-grid">
                <?php 
                $count = 0;
                foreach ($users as $user): 
                    if ($user['status'] !== 'active') continue;
                    $count++;
                ?>
                    <div class="barcode-card">
                        <div class="company-header">
                            <div class="company-name">ATTENDANCE SYSTEM</div>
                            <div class="company-subtitle">Employee ID Card</div>
                        </div>
                        
                        <div class="employee-info">
                            <div class="employee-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="employee-id">ID: <?php echo htmlspecialchars($user['employee_id']); ?></div>
                            <?php if ($user['department']): ?>
                                <div class="employee-dept"><?php echo htmlspecialchars($user['department']); ?></div>
                            <?php endif; ?>
                            <?php if ($user['position']): ?>
                                <div class="employee-dept"><?php echo htmlspecialchars($user['position']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="barcode-display">
                            <div class="barcode-number"><?php echo htmlspecialchars($user['barcode']); ?></div>
                            <img src="../api/qr_image.php?code=<?php echo urlencode($user['barcode']); ?>&scale=2"
                                 alt="QR code <?php echo htmlspecialchars($user['barcode']); ?>"
                                 style="max-width: 100%; background: #fff;">

                            <img src="../api/barcode_image.php?code=<?php echo urlencode($user['barcode']); ?>&scale=2"
                                 alt="Barcode <?php echo htmlspecialchars($user['barcode']); ?>"
                                 style="max-width: 100%; background: #fff; margin-top: 6px;">

                            <div class="instructions">
                                Scan either code at attendance terminal<br>
                                First scan = In • Second scan = Out
                            </div>
                            <div class="no-print" style="margin-top: 6px; font-size: 0.75rem;">
                                <a href="../api/qr_image.php?code=<?php echo urlencode($user['barcode']); ?>&download=1">QR PNG</a>
                                |
                                <a href="../api/barcode_image.php?code=<?php echo urlencode($user['barcode']); ?>&download=1">Barcode PNG</a>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Add page break after every 8 cards (4 rows of 2)
                    if ($count % 8 === 0): ?>
                        </div><div class="page-break"></div><div class="barcode-grid">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { 
        //     setTimeout(() => window.print(), 1000); 
        // }
    </script>
</body>
</html>