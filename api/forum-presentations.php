<?php
declare(strict_types=1);
$root = dirname(__DIR__) . '/reports/forum-2026/presentations';
$types = ['pdf' => 'application/pdf', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'ppt' => 'application/vnd.ms-powerpoint'];
$files = [];
if (is_dir($root)) {
    foreach (new DirectoryIterator($root) as $entry) {
        if (!$entry->isFile() || $entry->isLink()) continue;
        $name = $entry->getFilename();
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset($types[$extension])) continue;
        $handle = fopen($entry->getPathname(), 'rb');
        if (!$handle) continue;
        $signature = fread($handle, 8);
        fclose($handle);
        if ($extension === 'pdf' && strpos($signature, '%PDF-') !== 0) continue;
        if ($extension === 'pptx' && substr($signature, 0, 2) !== 'PK') continue;
        if ($extension === 'ppt' && $signature !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") continue;
        $files[$name] = ['name' => $name, 'format' => strtoupper($extension), 'bytes' => $entry->getSize(), 'url' => '/api/forum-presentations.php?file=' . rawurlencode($name)];
    }
}
uksort($files, 'strnatcasecmp');
header('X-Content-Type-Options: nosniff');
if (isset($_GET['file'])) {
    $name = is_string($_GET['file']) ? $_GET['file'] : '';
    if (!isset($files[$name])) { http_response_code(404); exit; }
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    header('Content-Type: ' . $types[$extension]);
    header('Content-Length: ' . $files[$name]['bytes']);
    header("Content-Disposition: attachment; filename=\"presentation." . $extension . "\"; filename*=UTF-8''" . rawurlencode($name));
    header('Cache-Control: public, max-age=3600');
    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') readfile($root . '/' . $name);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['files' => array_values($files)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
