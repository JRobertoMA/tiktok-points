<?php
/**
 * Endpoint para actualizar perfil de usuario
 * PUT /api/auth/profile.php
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
$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = getDB();

    // Obtener usuario actual
    $stmt = $db->prepare("SELECT id, username, email, password FROM users WHERE id = ?");
    $stmt->execute([$payload['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado']);
        exit;
    }

    $fields = [];
    $values = [];

    // Actualizar username si se proporciona
    if (!empty($input['username'])) {
        $newUsername = trim($input['username']);

        if (strlen($newUsername) < 3 || strlen($newUsername) > 50) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre de usuario debe tener entre 3 y 50 caracteres']);
            exit;
        }

        // Verificar que no esté en uso
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$newUsername, $user['id']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Este nombre de usuario ya está en uso']);
            exit;
        }

        $fields[] = "username = ?";
        $values[] = $newUsername;
    }

    // Actualizar email si se proporciona
    if (!empty($input['email'])) {
        $newEmail = trim(strtolower($input['email']));

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email inválido']);
            exit;
        }

        // Verificar que no esté en uso
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$newEmail, $user['id']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Este email ya está en uso']);
            exit;
        }

        $fields[] = "email = ?";
        $values[] = $newEmail;
    }

    // Cambiar contraseña si se proporciona
    if (!empty($input['new_password'])) {
        // Verificar contraseña actual
        if (empty($input['current_password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Debes proporcionar tu contraseña actual']);
            exit;
        }

        if (!password_verify($input['current_password'], $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'La contraseña actual es incorrecta']);
            exit;
        }

        if (strlen($input['new_password']) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'La nueva contraseña debe tener al menos 6 caracteres']);
            exit;
        }

        $fields[] = "password = ?";
        $values[] = password_hash($input['new_password'], PASSWORD_DEFAULT);
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay datos para actualizar']);
        exit;
    }

    // Ejecutar actualización
    $values[] = $user['id'];
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    // Obtener datos actualizados
    $stmt = $db->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $updatedUser = $stmt->fetch();

    // Generar nuevo token con datos actualizados
    $newToken = JWT::encode([
        'user_id' => $updatedUser['id'],
        'username' => $updatedUser['username'],
        'email' => $updatedUser['email']
    ]);

    echo json_encode([
        'message' => 'Perfil actualizado exitosamente',
        'user' => $updatedUser,
        'token' => $newToken
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
