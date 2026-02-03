<?php
/**
 * Endpoint para actualizar categoría
 * PUT /api/categories/update.php?id=X
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
    echo json_encode(['error' => 'ID de categoría requerido']);
    exit;
}

$categoryId = (int)$_GET['id'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = getDB();

    // Verificar que la categoría existe
    $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Categoría no encontrada']);
        exit;
    }

    // Construir query de actualización
    $fields = [];
    $values = [];

    if (isset($input['name']) && !empty(trim($input['name']))) {
        $fields[] = "name = ?";
        $values[] = trim($input['name']);
    }

    if (isset($input['icon'])) {
        $fields[] = "icon = ?";
        $values[] = $input['icon'];
    }

    if (isset($input['color'])) {
        $color = $input['color'];
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $fields[] = "color = ?";
            $values[] = $color;
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay campos para actualizar']);
        exit;
    }

    $values[] = $categoryId;
    $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?";

    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    // Obtener categoría actualizada
    $stmt = $db->prepare("SELECT c.*, u.username as created_by_username,
                                 (SELECT COUNT(*) FROM places WHERE category_id = c.id) as places_count
                          FROM categories c
                          LEFT JOIN users u ON c.created_by = u.id
                          WHERE c.id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();

    echo json_encode([
        'message' => 'Categoría actualizada exitosamente',
        'category' => $category
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
