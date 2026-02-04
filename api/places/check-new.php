<?php
/**
 * Endpoint para verificar nuevos lugares
 * GET /api/places/check-new.php?since=TIMESTAMP
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$payload = requireAuth();

try {
    $db = getDB();

    // Obtener timestamp desde el que buscar
    $since = isset($_GET['since']) ? $_GET['since'] : null;
    $excludeUserId = isset($_GET['exclude_user']) ? (int)$_GET['exclude_user'] : null;

    if (!$since) {
        echo json_encode(['new_places' => [], 'count' => 0]);
        exit;
    }

    // Buscar lugares creados después del timestamp, excluyendo los del usuario actual
    $sql = "SELECT p.id, p.name, p.created_at, u.username as created_by_username
            FROM places p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.created_at > ?";

    $params = [$since];

    if ($excludeUserId) {
        $sql .= " AND p.created_by != ?";
        $params[] = $excludeUserId;
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT 10";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $newPlaces = $stmt->fetchAll();

    echo json_encode([
        'new_places' => $newPlaces,
        'count' => count($newPlaces),
        'server_time' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
