<?php
declare(strict_types=1);
// Originals live on Timeweb in the rsync-excluded reports directory.
$root = dirname(__DIR__) . '/reports/forum-2026/photos';
$photos = [];
if (is_dir($root)) {
    foreach (new DirectoryIterator($root) as $entry) {
        if ($entry->isDot() || $entry->isLink() || !$entry->isFile()) continue;
        $name = $entry->getFilename();
        if (!preg_match('/\.(jpe?g|png|webp)$/i', $name)) continue;
        if (@getimagesize($entry->getPathname()) === false) continue;
        $photos[] = $name;
    }
}
natcasesort($photos);
$photos = array_values($photos);
if (isset($_GET['file'])) {
    $name = (string)$_GET['file'];
    if (!in_array($name, $photos, true)) { http_response_code(404); exit; }
    $path = $root . '/' . $name;
    $info = getimagesize($path);
    if (isset($_GET['thumb']) && function_exists('imagecreatefromstring') && ($info[0] * $info[1]) <= 16000000) {
        $cache = dirname($root) . '/thumbnails';
        if (is_dir($cache) || @mkdir($cache, 0755, true)) {
            $target = $cache . '/' . hash('sha256', $name . ':' . filemtime($path) . ':' . filesize($path)) . '.jpg';
            if (!is_file($target)) {
                $source = @imagecreatefromstring(file_get_contents($path));
                if ($source !== false) {
                    if (function_exists('exif_read_data') && preg_match('/\.jpe?g$/i', $name)) {
                        $exif = @exif_read_data($path);
                        $orientation = $exif['Orientation'] ?? 1;
                        if ($orientation === 3) $source = imagerotate($source, 180, 0);
                        elseif ($orientation === 6) $source = imagerotate($source, -90, 0);
                        elseif ($orientation === 8) $source = imagerotate($source, 90, 0);
                    }
                    $w = imagesx($source); $h = imagesy($source);
                    $ratio = min(1, 900 / max($w, $h));
                    $small = imagecreatetruecolor(max(1, (int)round($w * $ratio)), max(1, (int)round($h * $ratio)));
                    imagecopyresampled($small, $source, 0, 0, 0, 0, imagesx($small), imagesy($small), $w, $h);
                    $temp = tempnam($cache, 'photo-');
                    if ($temp !== false) { imagejpeg($small, $temp, 84); rename($temp, $target); }
                    imagedestroy($small); imagedestroy($source);
                }
            }
            if (is_file($target)) { header('Content-Type: image/jpeg'); header('Cache-Control: public, max-age=86400'); readfile($target); exit; }
        }
    }
    header('Content-Type: ' . $info['mime']);
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    if (isset($_GET['download'])) header("Content-Disposition: attachment; filename=\"forum-photo." . strtolower(pathinfo($name, PATHINFO_EXTENSION)) . "\"; filename*=UTF-8''" . rawurlencode($name));
    else header('Cache-Control: public, max-age=3600');
    readfile($path); exit;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
echo json_encode(['photos' => array_map(static function ($name) {
    $url = '/api/forum-photos.php?file=' . rawurlencode($name);
    return ['name' => $name, 'src' => $url, 'thumb' => $url . '&thumb=1', 'download' => $url . '&download=1'];
}, $photos)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
