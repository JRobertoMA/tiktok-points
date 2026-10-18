<?php
/**
 * Endpoint para subir archivos (fotos/videos)
 * POST /api/uploads/index.php?place_id=X
 * DELETE /api/uploads/index.php?id=X
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
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

    // POST - Subir archivo
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_GET['place_id']) || !is_numeric($_GET['place_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de lugar requerido']);
            exit;
        }

        $placeId = (int)$_GET['place_id'];

        // Verificar que el lugar existe
        $stmt = $db->prepare("SELECT id FROM places WHERE id = ?");
        $stmt->execute([$placeId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Lugar no encontrado']);
            exit;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No se recibió ningún archivo válido']);
            exit;
        }

        $file = $_FILES['file'];
        $mimeType = mime_content_type($file['tmp_name']);

        // Determinar tipo de archivo
        $fileType = null;
        if (in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            $fileType = 'image';
        } elseif (in_array($mimeType, ALLOWED_VIDEO_TYPES)) {
            $fileType = 'video';
        }

        if (!$fileType) {
            http_response_code(400);
            echo json_encode(['error' => 'Tipo de archivo no permitido. Solo se permiten imágenes (JPG, PNG, GIF, WebP) y videos (MP4, WebM)']);
            exit;
        }

        // Verificar tamaño
        if ($file['size'] > MAX_FILE_SIZE) {
            http_response_code(400);
            echo json_encode(['error' => 'El archivo es demasiado grande. Máximo: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB']);
            exit;
        }

        // Crear directorio del lugar si no existe
        $placeDir = UPLOAD_DIR . 'place_' . $placeId . '/';
        if (!is_dir($placeDir)) {
            mkdir($placeDir, 0755, true);
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '_' . time() . '.' . strtolower($extension);
        $relativePath = 'place_' . $placeId . '/' . $fileName;
        $fullPath = UPLOAD_DIR . $relativePath;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al guardar el archivo']);
            exit;
        }

        // Guardar en base de datos
        $stmt = $db->prepare("INSERT INTO place_media (place_id, file_path, file_type) VALUES (?, ?, ?)");
        $stmt->execute([$placeId, $relativePath, $fileType]);

        $mediaId = $db->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'message' => 'Archivo subido exitosamente',
            'media' => [
                'id' => $mediaId,
                'file_path' => $relativePath,
                'file_type' => $fileType
            ]
        ]);
        exit;
    }

    // DELETE - Eliminar archivo
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de media requerido']);
            exit;
        }

        $mediaId = (int)$_GET['id'];

        // Obtener info del archivo
        $stmt = $db->prepare("SELECT file_path FROM place_media WHERE id = ?");
        $stmt->execute([$mediaId]);
        $media = $stmt->fetch();

        if (!$media) {
            http_response_code(404);
            echo json_encode(['error' => 'Archivo no encontrado']);
            exit;
        }

        // Eliminar archivo físico
        $fullPath = UPLOAD_DIR . $media['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Eliminar de la base de datos
        $stmt = $db->prepare("DELETE FROM place_media WHERE id = ?");
        $stmt->execute([$mediaId]);

        echo json_encode(['message' => 'Archivo eliminado exitosamente']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => DEBUG_MODE ? $e->getMessage() : 'Error interno del servidor']);
}
