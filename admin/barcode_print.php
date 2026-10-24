<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_GET['id'] ?? null;
$user = null;

if ($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$user) {
    header('Location: users.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print QR Code - <?php echo htmlspecialchars($user['name']); ?></title>
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
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .print-area {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .barcode-card {
            border: 2px solid #333;
            border-radius: 10px;
            padding: 30px;
            margin: 20px 0;
            background: white;
            display: inline-block;
            min-width: 400px;
        }
        
        .company-header {
            margin-bottom: 20px;
        }
        
        .company-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .company-subtitle {
            font-size: 0.9rem;
            color: #666;
        }
        
        .employee-info {
            margin: 20px 0;
        }
        
        .employee-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .employee-id {
            font-size: 1rem;
            color: #666;
            margin-bottom: 3px;
        }
        
        .employee-dept {
            font-size: 0.9rem;
            color: #666;
        }
        
        .barcode-display {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        
        .barcode-visual {
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            line-height: 1;
            color: #000;
            background: white;
            padding: 10px;
            border: 1px solid #ddd;
            margin: 10px 0;
        }
        
        .instructions {
            margin-top: 20px;
            font-size: 0.8rem;
            color: #666;
            line-height: 1.4;
        }
        
        .controls {
            margin-bottom: 20px;
            text-align: center;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="controls no-print">
            <button onclick="window.print()" class="btn">🖨️ Print Card</button>
            <a href="../api/qr_image.php?code=<?php echo urlencode($user['barcode']); ?>&download=1" class="btn" style="background: #28a745;">⬇️ QR PNG</a>
            <a href="../api/barcode_image.php?code=<?php echo urlencode($user['barcode']); ?>&download=1" class="btn" style="background: #28a745;">⬇️ Barcode PNG</a>
            <a href="users.php" class="btn btn-secondary">← Back to Users</a>
        </div>
        
        <div class="print-area">
            <div class="barcode-card">
                <div class="company-header">
                    <div class="company-name">ATTENDANCE SYSTEM</div>
                    <div class="company-subtitle">Employee Identification Card</div>
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

                    <img src="../api/qr_image.php?code=<?php echo urlencode($user['barcode']); ?>&scale=3"
                         alt="QR code <?php echo htmlspecialchars($user['barcode']); ?>"
                         style="max-width: 100%; background: white; padding: 10px; border: 1px solid #ddd; margin: 10px 0;">

                    <div style="font-size: 0.8rem; color: #666; margin: 4px 0;">or scan the barcode:</div>

                    <img src="../api/barcode_image.php?code=<?php echo urlencode($user['barcode']); ?>&scale=3"
                         alt="Barcode <?php echo htmlspecialchars($user['barcode']); ?>"
                         style="max-width: 100%; background: white; padding: 10px; border: 1px solid #ddd; margin: 10px 0;">

                    <div class="instructions">
                        Scan either code at the attendance terminal<br>
                        First scan = Check In • Second scan = Check Out<br>
                        Keep this card with you at all times
                    </div>
                </div>
            </div>
        </div>

        <!-- Multiple copies for cutting -->
        <div class="print-area">
            <div class="barcode-card">
                <div class="company-header">
                    <div class="company-name">ATTENDANCE SYSTEM</div>
                    <div class="company-subtitle">Employee Identification Card</div>
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

                    <img src="../api/qr_image.php?code=<?php echo urlencode($user['barcode']); ?>&scale=3"
                         alt="QR code <?php echo htmlspecialchars($user['barcode']); ?>"
                         style="max-width: 100%; background: white; padding: 10px; border: 1px solid #ddd; margin: 10px 0;">

                    <div style="font-size: 0.8rem; color: #666; margin: 4px 0;">or scan the barcode:</div>

                    <img src="../api/barcode_image.php?code=<?php echo urlencode($user['barcode']); ?>&scale=3"
                         alt="Barcode <?php echo htmlspecialchars($user['barcode']); ?>"
                         style="max-width: 100%; background: white; padding: 10px; border: 1px solid #ddd; margin: 10px 0;">

                    <div class="instructions">
                        Scan either code at the attendance terminal<br>
                        First scan = Check In • Second scan = Check Out<br>
                        Keep this card with you at all times
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>