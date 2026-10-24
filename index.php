<?php
require_once 'includes/functions.php';

$message = '';
$messageType = '';

// Handle barcode scan
if ($_POST['barcode'] ?? '') {
    $barcode = trim($_POST['barcode']);
    
    if (empty($barcode)) {
        $message = 'Please scan a valid QR code';
        $messageType = 'error';
    } else {
        $user = getUserByBarcode($barcode);
        
        if (!$user) {
            $message = 'Invalid QR code or user not found';
            $messageType = 'error';
        } else {
            // Check last scan today
            $lastScan = getLastScanToday($user['id']);
            $scanType = 'in'; // Default to check-in
            
            // If last scan today was 'in', next should be 'out'
            if ($lastScan && $lastScan['scan_type'] === 'in') {
                $scanType = 'out';
            }
            
            if (recordAttendance($user['id'], $barcode, $scanType)) {
                $action = $scanType === 'in' ? 'checked in' : 'checked out';
                $message = "Welcome {$user['name']}! You have successfully $action.";
                $messageType = 'success';
            } else {
                $message = 'Failed to record attendance. Please try again.';
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .logo {
            font-size: 2.5em;
            color: #667eea;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .scan-area {
            background: #f8f9fa;
            border: 3px dashed #ddd;
            border-radius: 10px;
            padding: 40px 20px;
            margin-bottom: 30px;
        }
        
        .barcode-icon {
            font-size: 4em;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .scan-text {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 20px;
        }
        
        #barcodeInput {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1.1em;
            text-align: center;
            outline: none;
            transition: border-color 0.3s;
        }
        
        #barcodeInput:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        
        .admin-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .admin-link:hover {
            background: #5a6268;
        }
        
        .instructions {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
        }
        
        .instructions h3 {
            color: #0066cc;
            margin-bottom: 10px;
        }
        
        .instructions ul {
            margin-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 5px;
            color: #333;
        }
        
        .time-display {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📊</div>
        <h1>QR Code Attendance System</h1>
        <p class="subtitle">Scan your QR code to check in/out</p>
        
        <div class="time-display">
            <?php echo date('l, F j, Y - g:i A'); ?>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="scan-area">
            <div class="barcode-icon">🏷️</div>
            <p class="scan-text">Hold your QR code in front of the scanner</p>
            
            <form method="POST" id="attendanceForm">
                <input type="text" 
                       id="barcodeInput" 
                       name="barcode" 
                        placeholder="Scan QR code here..."
                       autocomplete="off" 
                       autofocus>
            </form>
        </div>
        
        <div class="instructions">
            <h3>Instructions:</h3>
            <ul>
                <li>Point your QR scanner at the QR code</li>
                <li>The scanner will automatically input the code</li>
                <li>First scan of the day = Check In</li>
                <li>Second scan = Check Out</li>
                <li>Contact admin if you encounter any issues</li>
            </ul>
        </div>
        
        <a href="admin/login.php" class="admin-link">Admin Panel</a>
    </div>
    
    <script>
        // Auto-focus barcode input
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeInput = document.getElementById('barcodeInput');
            
            // Keep focus on barcode input
            setInterval(function() {
                if (document.activeElement !== barcodeInput) {
                    barcodeInput.focus();
                }
            }, 1000);
            
            // Auto-submit when barcode is scanned
            barcodeInput.addEventListener('input', function() {
                // Most barcode scanners send data quickly and then trigger enter
                // We'll auto-submit after a short delay if no more input comes
                clearTimeout(window.barcodeTimeout);
                
                if (this.value.length >= 8) { // Minimum barcode length
                    window.barcodeTimeout = setTimeout(function() {
                        if (barcodeInput.value.trim()) {
                            document.getElementById('attendanceForm').submit();
                        }
                    }, 100); // 100ms delay
                }
            });
            
            // Also handle Enter key press (most scanners send this)
            barcodeInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (this.value.trim()) {
                        document.getElementById('attendanceForm').submit();
                    }
                }
            });
            
            // Clear input after message display
            <?php if ($message): ?>
                setTimeout(function() {
                    barcodeInput.value = '';
                    barcodeInput.focus();
                }, 3000);
            <?php endif; ?>
        });
    </script>
</body>
</html>