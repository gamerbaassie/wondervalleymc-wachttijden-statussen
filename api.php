<?php
// Plaats dit bestand op je website als api.php.
// Dezelfde API-key moet in plugin/config.yml en hieronder staan.

$API_KEY = 'tWhB4u4hEJajDaox1Ln2';
$dataFile = __DIR__ . '/rides.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    if (!file_exists($dataFile)) {
        echo json_encode(['rides' => []], JSON_UNESCAPED_UNICODE);
    } else {
        readfile($dataFile);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals($API_KEY, $providedKey)) {
    http_response_code(401);
    exit('Unauthorized');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    exit('Invalid JSON');
}

$current = ['rides' => []];
if (file_exists($dataFile)) {
    $decoded = json_decode(file_get_contents($dataFile), true);
    if (is_array($decoded)) $current = $decoded;
}

if (($input['action'] ?? '') === 'replace') {
    $current['rides'] = $input['rides'] ?? [];
} elseif (($input['action'] ?? '') === 'upsert' && isset($input['ride']['id'])) {
    $id = $input['ride']['id'];
    $found = false;
    foreach ($current['rides'] as &$ride) {
        if (($ride['id'] ?? '') === $id) {
            $ride = $input['ride'];
            $found = true;
            break;
        }
    }
    unset($ride);
    if (!$found) $current['rides'][] = $input['ride'];
} else {
    http_response_code(400);
    exit('Unknown action');
}

file_put_contents(
    $dataFile,
    json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);
?>
