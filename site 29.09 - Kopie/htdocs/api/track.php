<?php
require __DIR__ . '/../lib/utils.php';

// Simple visit counter: aggregates per day and a total.
// Privacy: stores no IPs or personal data.

ensure_storage();
$file = cfg()['STORAGE'] . '/visits.json';

function read_visits($file){
    if (!file_exists($file)) return ['total'=>0,'days'=>[]];
    $raw = @file_get_contents($file);
    $json = json_decode($raw, true);
    if (!is_array($json)) return ['total'=>0,'days'=>[]];
    $json['total'] = isset($json['total']) && is_numeric($json['total']) ? (int)$json['total'] : 0;
    $json['days']  = isset($json['days']) && is_array($json['days']) ? $json['days'] : [];
    return $json;
}

function write_visits($file, $data){
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $tmp = $file . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_SLASHES));
    @rename($tmp, $file);
}

$mode = strtolower($_GET['mode'] ?? $_POST['mode'] ?? 'hit');

// Concurrency-safe update with flock
$fp = fopen($file, 'c+');
if ($fp === false) {
    json_response(['error'=>'cannot open counter'], 500);
}
flock($fp, LOCK_EX);
$current = read_visits($file);

if ($mode === 'hit' || $mode === '') {
    $today = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d');
    $current['total'] = (int)($current['total'] ?? 0) + 1;
    $days = $current['days'] ?? [];
    $days[$today] = isset($days[$today]) ? (int)$days[$today] + 1 : 1;
    // optional: trim very old entries to keep file small (keep 400 days)
    if (count($days) > 420) {
        ksort($days);
        $days = array_slice($days, -420, null, true);
    }
    $current['days'] = $days;
    write_visits($file, $current);
}

// compute last 30 days sum (UTC-based)
$sum30 = 0;
$days = $current['days'] ?? [];
if ($days) {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    for ($i=0; $i<30; $i++) {
        $d = (clone $now)->modify("-$i day")->format('Y-m-d');
        if (isset($days[$d])) $sum30 += (int)$days[$d];
    }
}
flock($fp, LOCK_UN);
fclose($fp);

json_response(['ok'=>true,'total'=>(int)($current['total'] ?? 0),'last30'=>$sum30]);

