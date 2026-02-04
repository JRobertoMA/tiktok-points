<?php
/**
 * Middleware de autenticación JWT
 * Implementación simple sin dependencias externas
 */

require_once __DIR__ . '/../../config.php';

class JWT {

    /**
     * Codifica en Base64 URL-safe
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica Base64 URL-safe
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Genera un token JWT
     */
    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRY;
        $payloadJson = json_encode($payload);

        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payloadJson);

        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET, true);
        $base64Signature = self::base64UrlEncode($signature);

        return "$base64Header.$base64Payload.$base64Signature";
    }

    /**
     * Decodifica y valida un token JWT
     */
    public static function decode($token) {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        // Verificar firma
        $signature = self::base64UrlDecode($base64Signature);
        $expectedSignature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Decodificar payload
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);

        if (!$payload) {
            return null;
        }

        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }
}

/**
 * Obtiene el token del header Authorization o X-Auth-Token
 */
function getBearerToken() {
    // Primero intentar X-Auth-Token (funciona en IONOS/shared hosting)
    if (isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        return trim($_SERVER['HTTP_X_AUTH_TOKEN']);
    }

    // Fallback a Authorization header tradicional
    $headers = null;

    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(
            array_map('ucwords', array_keys($requestHeaders)),
            array_values($requestHeaders)
        );
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Middleware para verificar autenticación
 * Devuelve los datos del usuario o termina con error 401
 */
function requireAuth() {
    $token = getBearerToken();

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Token no proporcionado']);
        exit;
    }

    $payload = JWT::decode($token);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        exit;
    }

    return $payload;
}

/**
 * Middleware opcional - devuelve usuario si está autenticado, null si no
 */
function optionalAuth() {
    $token = getBearerToken();

    if (!$token) {
        return null;
    }

    return JWT::decode($token);
}
