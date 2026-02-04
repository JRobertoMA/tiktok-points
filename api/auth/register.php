<?php
/**
 * Endpoint de registro de usuarios
 * POST /api/auth/register.php
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
if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Todos los campos son requeridos']);
    exit;
}

$username = trim($input['username']);
$email = trim(strtolower($input['email']));
$password = $input['password'];

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email inválido']);
    exit;
}

// Validar longitud de username
if (strlen($username) < 3 || strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(['error' => 'El nombre de usuario debe tener entre 3 y 50 caracteres']);
    exit;
}

// Validar longitud de password
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

try {
    $db = getDB();

    // Verificar si el usuario ya existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'El email o nombre de usuario ya está registrado']);
        exit;
    }

    // Crear el usuario
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword]);

    $userId = $db->lastInsertId();

    // Generar token JWT
    $token = JWT::encode([
        'user_id' => $userId,
        'username' => $username,
        'email' => $email
    ]);

    echo json_encode([
        'message' => 'Usuario registrado exitosamente',
        'token' => $token,
        'user' => [
            'id' => $userId,
            'username' => $username,
            'email' => $email
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
