<?php
/**
 * Endpoint para eliminar categoría
 * DELETE /api/categories/delete.php?id=X
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
    echo json_encode(['error' => 'ID de categoría requerido']);
    exit;
}

$categoryId = (int)$_GET['id'];

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

    // Verificar si hay lugares usando esta categoría
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM places WHERE category_id = ?");
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        // Poner categoría como NULL en los lugares
        $stmt = $db->prepare("UPDATE places SET category_id = NULL WHERE category_id = ?");
        $stmt->execute([$categoryId]);
    }

    // Eliminar categoría
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);

    echo json_encode(['message' => 'Categoría eliminada exitosamente']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
