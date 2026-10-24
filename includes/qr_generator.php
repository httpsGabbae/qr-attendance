<?php
// QR code generation for the attendance system (replaces 1D barcodes).
//
// Encoding: phpqrcode (LGPL v3, based on libqrencode) vendored under
// includes/qrlib/ — pure PHP, no composer, no CDN, no GD required.
// Rendering: the frame matrix is drawn with the dependency-free PNG
// encoder in includes/barcode_generator.php, so this works on any
// PHP build (CLI, Apache with or without GD).
//
// Attendance codes (e.g. EMP856113) are uppercase alphanumeric, so they
// encode in QR alphanumeric mode: tiny version-1 symbols that scan fast.

require_once __DIR__ . '/qrlib/qrlib.php';
require_once __DIR__ . '/barcode_generator.php';

// Keep payloads small so symbols stay at low versions (easy to scan).
function qr_sanitize($text) {
    $text = trim((string)$text);
    if ($text === '') {
        throw new Exception('Empty QR payload.');
    }
    if (strlen($text) > 256) {
        throw new Exception('QR payload too long (max 256 chars).');
    }
    return $text;
}

// Returns [widthModules, rows] where each row is a string of '0'/'1'.
// ('1' = black module. Uses QRtools::binarize, same as the library's
// own PNG/SVG renderers.)
function qr_matrix($text, $level = QR_ECLEVEL_M) {
    $text = qr_sanitize($text);

    $code = new QRcode();
    // version 0 = auto, hint 8-bit input, casesensitive=false lets the
    // splitter use compact alphanumeric mode for codes like EMP856113.
    $code->encodeString($text, 0, $level, QR_MODE_8, false);

    if (empty($code->data) || empty($code->width)) {
        throw new Exception('QR encoding failed.');
    }
    return [$code->width, QRtools::binarize($code->data)];
}

// Returns raw PNG bytes for the QR symbol of $text.
function qr_to_png($text, $scale = 6, $margin = 4) {
    list($width, $frame) = qr_matrix($text);
    $scale = max(2, min(12, (int)$scale));
    $margin = max(0, min(10, (int)$margin)); // quiet zone, spec minimum 4

    $full = $width + 2 * $margin;
    $px = $full * $scale;
    $whiteRow = str_repeat("\xFF", $px);
    $rows = [];

    // Top quiet zone.
    for ($i = 0; $i < $margin * $scale; $i++) { $rows[] = $whiteRow; }

    for ($y = 0; $y < $width; $y++) {
        $line = str_repeat("\xFF", $margin * $scale);
        $rowStr = $frame[$y];
        for ($x = 0; $x < $width; $x++) {
            $line .= str_repeat(($rowStr[$x] === '1') ? "\x00" : "\xFF", $scale);
        }
        $line .= str_repeat("\xFF", $margin * $scale);
        for ($i = 0; $i < $scale; $i++) { $rows[] = $line; }
    }

    // Bottom quiet zone.
    for ($i = 0; $i < $margin * $scale; $i++) { $rows[] = $whiteRow; }

    return barcode_encode_png($px, count($rows), $rows);
}
