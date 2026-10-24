<?php
require_once __DIR__ . '/../config/db.php';

// Generate a unique, Code 39-safe barcode for a user.
// Code 39 reads on any 1D scanner (incl. MRBoss POS R45), so the value
// uses only A-Z and 0-9. Kept short (EMP + 6 digits, 9 chars) so the
// printed bars stay narrow enough to scan easily.
function generateBarcode() {
    global $pdo;

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $barcode = 'EMP' . str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE barcode = ? LIMIT 1");
            $stmt->execute([$barcode]);
            if (!$stmt->fetch()) {
                return $barcode;
            }
        } catch (Throwable $e) {
            return $barcode;
        }
    }
    // Extremely unlikely (10 collisions in a 1M space): fall back to
    // timestamp-based value, still Code 39-safe.
    return 'EMP' . date('His') . str_pad((string)rand(0, 999), 3, '0', STR_PAD_LEFT);
}

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Admin login function
function adminLogin($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, password FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $username;
        return true;
    }
    return false;
}

// Get user by id
function getUserById($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user by barcode
function getUserByBarcode($barcode) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE barcode = ? AND status = 'active'");
    $stmt->execute([$barcode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Record attendance
function recordAttendance($userId, $barcode, $scanType) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO attendance (user_id, barcode, scan_type, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $userId,
        $barcode,
        $scanType,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Get last scan for user today
function getLastScanToday($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT scan_type, scan_time 
        FROM attendance 
        WHERE user_id = ? AND DATE(scan_time) = CURDATE() 
        ORDER BY scan_time DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get attendance report
function getAttendanceReport($startDate = null, $endDate = null, $userId = null) {
    global $pdo;
    
    $where = "WHERE 1=1";
    $params = [];
    
    if ($startDate) {
        $where .= " AND DATE(a.scan_time) >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $where .= " AND DATE(a.scan_time) <= ?";
        $params[] = $endDate;
    }
    
    if ($userId) {
        $where .= " AND a.user_id = ?";
        $params[] = $userId;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            u.name, 
            u.employee_id, 
            u.department,
            a.scan_type,
            a.scan_time,
            DATE(a.scan_time) as scan_date
        FROM attendance a 
        JOIN users u ON a.user_id = u.id 
        $where 
        ORDER BY a.scan_time DESC
    ");
    
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all users
function getAllUsers() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add new user
function addUser($employeeId, $name, $email, $department, $position) {
    global $pdo;
    
    $barcode = generateBarcode();
    
    $stmt = $pdo->prepare("
        INSERT INTO users (employee_id, name, email, department, position, barcode) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$employeeId, $name, $email, $department, $position, $barcode])) {
        return [
            'success' => true,
            'user_id' => $pdo->lastInsertId(),
            'barcode' => $barcode
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to add user'];
}

// Format datetime for display
function formatDateTime($datetime) {
    return date('M j, Y g:i A', strtotime($datetime));
}

// Get attendance summary for today
function getTodayAttendanceSummary() {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN scan_type = 'in' THEN user_id END) as checked_in,
            COUNT(DISTINCT user_id) as total_scans,
            (SELECT COUNT(*) FROM users WHERE status = 'active') as total_users
        FROM attendance 
        WHERE DATE(scan_time) = CURDATE()
    ");
    
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>