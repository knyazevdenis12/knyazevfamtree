<?php
// Серверное хранилище данных родословной.
// save.php и data.json должны находиться в одной папке с index.html.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$dataFile = __DIR__ . '/data.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!is_file($dataFile)) {
        echo json_encode(['version'=>'3.4','people'=>[],'connections'=>[],'positions'=>new stdClass()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $raw = file_get_contents($dataFile);
    if ($raw === false) {
        http_response_code(500);
        echo json_encode(['error'=>'Не удалось прочитать data.json'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo $raw;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['people']) || !is_array($data['people'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Некорректные данные'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = [
    'version' => '3.4',
    'updatedAt' => date('c'),
    'people' => $data['people'],
    'connections' => isset($data['connections']) && is_array($data['connections']) ? $data['connections'] : [],
    'positions' => isset($data['positions']) && is_array($data['positions']) ? $data['positions'] : new stdClass()
];

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false) {
    http_response_code(500);
    echo json_encode(['error'=>'Не удалось сформировать JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (file_put_contents($dataFile, $json, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error'=>'Нет прав на запись data.json'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok'=>true,'updatedAt'=>$payload['updatedAt']], JSON_UNESCAPED_UNICODE);
