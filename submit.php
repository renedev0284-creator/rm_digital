<?php
// ── Configuración ──────────────────────────────────────────────────────────
// TODO: Actualiza estos valores antes de publicar
const WEBHOOK_URL    = 'http://localhost/portalrm/api/leads_webhook.php';
const WEBHOOK_TOKEN  = 'rmdigital_local_2025';
const ALLOWED_ORIGIN = 'https://rmdigital.net';
// ──────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');

// CORS — solo permite tu propio dominio en producción
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === ALLOWED_ORIGIN || str_starts_with($origin, 'http://localhost')) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']);
    exit;
}

$nombre = trim($body['nombre'] ?? '');
if (!$nombre) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'El nombre es obligatorio.']);
    exit;
}

// Reenviar al webhook de portalrm
$payload = json_encode([
    'token'    => WEBHOOK_TOKEN,
    'nombre'   => $nombre,
    'correo'   => trim($body['correo']   ?? ''),
    'telefono' => trim($body['telefono'] ?? ''),
    'negocio'  => trim($body['negocio']  ?? ''),
    'mensaje'  => trim($body['mensaje']  ?? ''),
]);

$ch = curl_init(WEBHOOK_URL);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error || $httpCode >= 500) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'msg' => 'Error interno. Por favor intenta de nuevo.']);
    exit;
}

$result = json_decode($response, true);
if (!empty($result['ok'])) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => $result['msg'] ?? 'No se pudo procesar el formulario.']);
}
