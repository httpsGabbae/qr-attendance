<?php
// Pure-PHP Code 39 barcode generator. No composer, no CDN, no dependencies.
// Uses GD when available, otherwise falls back to a hand-encoded PNG
// (only needs zlib, which ships with PHP core).
// Output is readable by the MRBoss POS R45 (keyboard-wedge HID).

// Code 39 patterns: 9 elements each (bar, space alternating, starting with
// a bar). 'n' = narrow, 'w' = wide (3x narrow). Includes start/stop '*'.
function barcode_code39_patterns() {
    static $patterns = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw',
        '3' => 'wnwwnnnnn', '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn',
        '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw', '8' => 'wnnwnnwnn',
        '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn',
        'F' => 'nnwnwwnnn', 'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn',
        'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn', 'K' => 'wnnnnnnww',
        'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww',
        'R' => 'wnnnnnwwn', 'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn',
        'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw', 'W' => 'wwwnnnnnn',
        'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn',
        '*' => 'nwnnwnwnn', '$' => 'nwnwnwnnn', '/' => 'nwnwnnnwn',
        '+' => 'nwnnnwnwn', '%' => 'nnwnwnwnn',
    ];
    return $patterns;
}

// Keep only Code 39-readable characters. Generated barcodes (EMP + digits)
// are always safe; this guards manual input.
function barcode_sanitize($code) {
    $code = strtoupper(trim((string)$code));
    $code = preg_replace('/[^0-9A-Z \-\.\$\/\+\%]/', '-', $code);
    $code = substr($code, 0, 64);
    if ($code === '' || $code === false) { $code = 'INVALID'; }
    return $code;
}

// Build the bar/space run-length sequence for a sanitized string.
// Returns array of [isBar, widthModules] plus total width in modules.
function barcode_code39_runs($code) {
    $patterns = barcode_code39_patterns();
    $full = '*' . $code . '*';
    $runs = [];
    $len = strlen($full);
    for ($c = 0; $c < $len; $c++) {
        $ch = $full[$c];
        if (!isset($patterns[$ch])) { continue; }
        $pat = $patterns[$ch];
        for ($i = 0; $i < 9; $i++) {
            $modules = ($pat[$i] === 'w') ? 3 : 1;
            $runs[] = [($i % 2 === 0), $modules];
        }
        // Inter-character narrow space (except after last char).
        if ($c < $len - 1) { $runs[] = [false, 1]; }
    }
    // Quiet zones: 10 narrow modules each side.
    array_unshift($runs, [false, 10]);
    $runs[] = [false, 10];
    $total = 0;
    foreach ($runs as $r) { $total += $r[1]; }
    return [$runs, $total];
}

function barcode_png_chunk($type, $data) {
    $chunk = $type . $data;
    return pack('N', strlen($data)) . $chunk . pack('N', crc32($chunk));
}

// Minimal grayscale PNG encoder (no GD needed). $rows is an array of
// strings, one per scanline, each byte = 0 (black) or 255 (white).
function barcode_encode_png($width, $height, array $rows) {
    $raw = '';
    foreach ($rows as $row) { $raw .= "\x00" . $row; }
    $ihdr = pack('NNCCCCC', $width, $height, 8, 0, 0, 0, 0);
    $png = "\x89PNG\r\n\x1a\n";
    $png .= barcode_png_chunk('IHDR', $ihdr);
    $png .= barcode_png_chunk('IDAT', gzcompress($raw));
    $png .= barcode_png_chunk('IEND', '');
    return $png;
}

// Main entry point. Returns raw PNG bytes for the given code value.
function barcode_to_png($code, $scale = 3, $barHeight = 90, $showText = true) {
    $code = barcode_sanitize($code);
    $scale = max(1, min(6, (int)$scale));
    $barHeight = max(40, min(200, (int)$barHeight));
    list($runs, $modules) = barcode_code39_runs($code);
    $width = $modules * $scale;

    // GD path (preferred: includes human-readable text).
    if (extension_loaded('gd') && function_exists('imagecreatetruecolor')) {
        $textH = $showText ? 22 : 8;
        $height = $barHeight + $textH;
        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $white);
        $x = 0;
        foreach ($runs as $r) {
            list($isBar, $mods) = $r;
            $w = $mods * $scale;
            if ($isBar) {
                imagefilledrectangle($img, $x, 0, $x + $w - 1, $barHeight - 1, $black);
            }
            $x += $w;
        }
        if ($showText) {
            $font = 5; // largest built-in GD font, no TTF needed
            $tw = imagefontwidth($font) * strlen($code);
            $tx = max(0, (int)(($width - $tw) / 2));
            imagestring($img, $font, $tx, $barHeight + 4, $code, $black);
        }
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);
        return $png;
    }

    // Fallback path: hand-encoded PNG, bars only (no text without GD).
    $height = $barHeight + 8;
    $whiteRow = str_repeat("\xFF", $width);
    $rows = [];
    $barRow = '';
    foreach ($runs as $r) {
        list($isBar, $mods) = $r;
        $barRow .= str_repeat($isBar ? "\x00" : "\xFF", $mods * $scale);
    }
    for ($y = 0; $y < $barHeight; $y++) { $rows[] = $barRow; }
    for ($y = $barHeight; $y < $height; $y++) { $rows[] = $whiteRow; }
    return barcode_encode_png($width, $height, $rows);
}
