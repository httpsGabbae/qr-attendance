<?php
// Serves a Code 39 1D barcode PNG for an attendance code.
// Usage:
//   api/barcode_image.php?code=EMP856113   (direct value)
//   api/barcode_image.php?id=7              (lookup users.barcode)
//   ...&download=1                          (force Save-as-PNG)
//   ...&scale=3                             (1-6, default 3)
// Pure PHP, no dependencies. Uses GD when available, otherwise the
// built-in fallback encoder in includes/barcode_generator.php.

require_once __DIR__ . '/../includes/barcode_generator.php';

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
    echo 'Missing barcode code. Use ?code=VALUE or ?id=USER_ID';
    exit();
}

$scale = isset($_GET['scale']) ? max(1, min(6, (int)$_GET['scale'])) : 3;

try {
    $png = barcode_to_png($code, $scale);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Failed to generate barcode.';
    exit();
}

$safe = preg_replace('/[^0-9A-Za-z\-_]/', '_', barcode_sanitize($code));
$filename = 'barcode-' . $safe . '.png';

header('Content-Type: image/png');
header('Content-Length: ' . strlen($png));
header('Cache-Control: public, max-age=86400');
if (isset($_GET['download'])) {
    header('Content-Disposition: attachment; filename="' . $filename . '"');
} else {
    header('Content-Disposition: inline; filename="' . $filename . '"');
}
echo $png;
