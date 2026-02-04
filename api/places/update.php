<?php
/**
 * Endpoint para actualizar lugar
 * PUT /api/places/update.php?id=X
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
$input = json_decode(file_get_contents('php://input'), true);

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

    // Construir query de actualización dinámicamente
    $fields = [];
    $values = [];

    $allowedFields = ['tiktok_link', 'category_id', 'name', 'address', 'rating',
                      'latitude', 'longitude', 'notes', 'visited', 'visit_date'];

    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $input)) {
            $fields[] = "$field = ?";
            $values[] = $input[$field];
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay campos para actualizar']);
        exit;
    }

    $values[] = $placeId;
    $sql = "UPDATE places SET " . implode(', ', $fields) . " WHERE id = ?";

    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    // Obtener lugar actualizado
    $stmt = $db->prepare("SELECT p.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                                 u.username as created_by_username
                          FROM places p
                          LEFT JOIN categories c ON p.category_id = c.id
                          LEFT JOIN users u ON p.created_by = u.id
                          WHERE p.id = ?");
    $stmt->execute([$placeId]);
    $place = $stmt->fetch();

    // Obtener media
    $stmtMedia = $db->prepare("SELECT id, file_path, file_type FROM place_media WHERE place_id = ?");
    $stmtMedia->execute([$placeId]);
    $place['media'] = $stmtMedia->fetchAll();

    echo json_encode([
        'message' => 'Lugar actualizado exitosamente',
        'place' => $place
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
