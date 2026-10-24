<?php
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    http_response_code(403);
    exit('Access denied');
}

// Get parameters
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$userId = $_GET['user_id'] ?? null;

// Get attendance data
$attendanceRecords = getAttendanceReport($startDate, $endDate, $userId);

// Process data for CSV export
$csvData = [];
$processedRecords = [];

// Group by date and employee
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

// Convert to CSV format
foreach ($processedRecords as $record) {
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
            $status = 'Incomplete';
        }
    }
    
    $csvData[] = [
        'Date' => $record['date'],
        'Employee ID' => $record['employee_id'],
        'Employee Name' => $record['name'],
        'Department' => $record['department'] ?: 'N/A',
        'Check In' => $record['check_in'] ? date('Y-m-d H:i:s', strtotime($record['check_in'])) : '',
        'Check Out' => $record['check_out'] ? date('Y-m-d H:i:s', strtotime($record['check_out'])) : '',
        'Hours Worked' => $hoursWorked,
        'Status' => $status
    ];
}

// Sort by date and name
usort($csvData, function($a, $b) {
    if ($a['Date'] === $b['Date']) {
        return strcmp($a['Employee Name'], $b['Employee Name']);
    }
    return strcmp($b['Date'], $a['Date']);
});

// Generate filename
$filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '_' . date('Y-m-d_H-i-s') . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
if (!empty($csvData)) {
    fputcsv($output, array_keys($csvData[0]));
    
    // Write data rows
    foreach ($csvData as $row) {
        fputcsv($output, $row);
    }
} else {
    // Empty file with headers only
    fputcsv($output, ['Date', 'Employee ID', 'Employee Name', 'Department', 'Check In', 'Check Out', 'Hours Worked', 'Status']);
    fputcsv($output, ['No data found for selected date range']);
}

fclose($output);
exit();
?>