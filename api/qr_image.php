<?php
// Serves a QR code PNG for an attendance code.
// Usage:
//   api/qr_image.php?code=EMP856113   (direct value)
//   api/qr_image.php?id=7              (lookup users.barcode)
//   ...&download=1                    (force Save-as-PNG)
//   ...&scale=6                       (2-12, default 6)
// Pure PHP, no dependencies, no GD required.

require_once __DIR__ . '/../includes/qr_generator.php';

$code = $_GET['code'] ?? null;

// Lookup by user id when no direct code is given.
if (($code === null || $code === '') && isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    try {
        require_once __DIR__ . '/../config/db.php';
        $stmt = $pdo->prepare("SELECT barcode FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_GET['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['barcode'])) {
            $code = $row['barcode'];
        }
    } catch (Throwable $e) {
        $code = null;
    }
}

if ($code === null || trim((string)$code) === '') {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Missing code. Use ?code=VALUE or ?id=USER_ID';
    exit();
}

$scale = isset($_GET['scale']) ? max(2, min(12, (int)$_GET['scale'])) : 6;

try {
    $png = qr_to_png($code, $scale);
} catch (Throwable $e) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Failed to generate QR code.';
    exit();
}

$safe = preg_replace('/[^0-9A-Za-z\-_]/', '_', trim((string)$code));
$filename = 'qr-' . $safe . '.png';

header('Content-Type: image/png');
header('Content-Length: ' . strlen($png));
header('Cache-Control: public, max-age=86400');
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    header('Content-Disposition: inline; filename="' . $filename . '"');
}
echo $png;
