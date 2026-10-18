<?php
/**
 * TikTok Points - Configuración General
 * Modifica estos valores según tu hosting IONOS
 */

// Configuración de Base de Datos
define('DB_HOST', 'localhost');          // Host de MySQL (normalmente localhost en IONOS)
define('DB_NAME', 'tiktok_points');       // Nombre de la base de datos
define('DB_USER', 'tu_usuario');          // Usuario de MySQL
define('DB_PASS', 'tu_contraseña');       // Contraseña de MySQL

// Configuración JWT
define('JWT_SECRET', 'cambia_esto_por_una_clave_secreta_muy_larga_y_segura_123!@#');
define('JWT_EXPIRY', 86400 * 7); // 7 días en segundos

// Configuración de Uploads
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/quicktime']);

// URL base (cambiar según tu dominio)
define('BASE_URL', 'https://tu-dominio.com/tiktok_points');

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Modo debug (cambiar a false en producción)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
