<?php
/**
 * Endpoint de login
 * POST /api/auth/login.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validar campos requeridos
if (empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email y contraseña son requeridos']);
    exit;
}

$email = trim(strtolower($input['email']));
$password = $input['password'];

try {
    $db = getDB();

    // Buscar usuario por email
    $stmt = $db->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Email o contraseña incorrectos']);
        exit;
    }

    // Generar token JWT
    $token = JWT::encode([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ]);

    echo json_encode([
        'message' => 'Login exitoso',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
