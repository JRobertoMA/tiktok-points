<?php
/**
 * Endpoint de categorías
 * GET /api/categories/index.php - Listar todas las categorías
 * POST /api/categories/index.php - Crear nueva categoría
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$payload = requireAuth();

try {
    $db = getDB();

    // GET - Listar categorías
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $db->prepare("SELECT c.*, u.username as created_by_username,
                                     (SELECT COUNT(*) FROM places WHERE category_id = c.id) as places_count
                              FROM categories c
                              LEFT JOIN users u ON c.created_by = u.id
                              ORDER BY c.name ASC");
        $stmt->execute();
        $categories = $stmt->fetchAll();

        echo json_encode(['categories' => $categories]);
        exit;
    }

    // POST - Crear categoría
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es requerido']);
            exit;
        }

        $name = trim($input['name']);
        $icon = $input['icon'] ?? '📍';
        $color = $input['color'] ?? '#3498db';

        // Validar formato de color
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#3498db';
        }

        $stmt = $db->prepare("INSERT INTO categories (name, icon, color, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $icon, $color, $payload['user_id']]);

        $categoryId = $db->lastInsertId();

        $stmt = $db->prepare("SELECT c.*, u.username as created_by_username
                              FROM categories c
                              LEFT JOIN users u ON c.created_by = u.id
                              WHERE c.id = ?");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        $category['places_count'] = 0;

        http_response_code(201);
        echo json_encode([
            'message' => 'Categoría creada exitosamente',
            'category' => $category
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
