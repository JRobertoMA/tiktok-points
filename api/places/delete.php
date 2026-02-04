<?php
/**
 * Endpoint para eliminar lugar
 * DELETE /api/places/delete.php?id=X
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$payload = requireAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de lugar requerido']);
    exit;
}

$placeId = (int)$_GET['id'];

try {
    $db = getDB();

    // Verificar que el lugar existe
    $stmt = $db->prepare("SELECT id FROM places WHERE id = ?");
    $stmt->execute([$placeId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Lugar no encontrado']);
        exit;
    }

    // Obtener archivos de media para eliminarlos
    $stmt = $db->prepare("SELECT file_path FROM place_media WHERE place_id = ?");
    $stmt->execute([$placeId]);
    $mediaFiles = $stmt->fetchAll();

    // Eliminar archivos físicos
    foreach ($mediaFiles as $media) {
        $filePath = __DIR__ . '/../../uploads/' . $media['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // Eliminar lugar (media se elimina por CASCADE)
    $stmt = $db->prepare("DELETE FROM places WHERE id = ?");
    $stmt->execute([$placeId]);

    echo json_encode(['message' => 'Lugar eliminado exitosamente']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
