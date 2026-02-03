<?php
/**
 * Endpoint de lugares
 * GET /api/places/index.php - Listar todos los lugares
 * POST /api/places/index.php - Crear nuevo lugar
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$payload = requireAuth();

try {
    $db = getDB();

    // GET - Listar lugares
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT p.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                       u.username as created_by_username,
                       (SELECT COUNT(*) FROM place_media WHERE place_id = p.id) as media_count
                FROM places p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                ORDER BY p.created_at DESC";

        // Filtros opcionales
        $params = [];
        $where = [];

        if (isset($_GET['visited'])) {
            $where[] = "p.visited = ?";
            $params[] = $_GET['visited'] === 'true' ? 1 : 0;
        }

        if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
            $where[] = "p.category_id = ?";
            $params[] = $_GET['category_id'];
        }

        if (!empty($where)) {
            $sql = str_replace('ORDER BY', 'WHERE ' . implode(' AND ', $where) . ' ORDER BY', $sql);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $places = $stmt->fetchAll();

        // Obtener media para cada lugar
        foreach ($places as &$place) {
            $stmtMedia = $db->prepare("SELECT id, file_path, file_type FROM place_media WHERE place_id = ?");
            $stmtMedia->execute([$place['id']]);
            $place['media'] = $stmtMedia->fetchAll();
        }

        echo json_encode(['places' => $places]);
        exit;
    }

    // POST - Crear lugar
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validar campos requeridos
        if (empty($input['name']) || !isset($input['latitude']) || !isset($input['longitude'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre y coordenadas son requeridos']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO places
            (tiktok_link, category_id, name, address, rating, latitude, longitude, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $input['tiktok_link'] ?? null,
            $input['category_id'] ?? null,
            trim($input['name']),
            $input['address'] ?? null,
            $input['rating'] ?? 0,
            $input['latitude'],
            $input['longitude'],
            $input['notes'] ?? null,
            $payload['user_id']
        ]);

        $placeId = $db->lastInsertId();

        // Obtener el lugar creado con info completa
        $stmt = $db->prepare("SELECT p.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                                     u.username as created_by_username
                              FROM places p
                              LEFT JOIN categories c ON p.category_id = c.id
                              LEFT JOIN users u ON p.created_by = u.id
                              WHERE p.id = ?");
        $stmt->execute([$placeId]);
        $place = $stmt->fetch();
        $place['media'] = [];

        http_response_code(201);
        echo json_encode([
            'message' => 'Lugar creado exitosamente',
            'place' => $place
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
