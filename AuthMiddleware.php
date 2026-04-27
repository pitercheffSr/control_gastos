<?php

class AuthMiddleware {

    /**
     * Verifica si hay una sesión activa para endpoints de la API.
     * Si no la hay, finaliza la ejecución y devuelve un error 401.
     * @return int El ID del usuario autenticado.
     */
    public static function checkAPI(): int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id'])) {
            self::sendError(401, 'No autorizado.');
        }

        return (int) $_SESSION['usuario_id'];
    }

    /**
     * Verifica que el usuario tenga sesión Y además sea administrador.
     * @return int El ID del administrador autenticado.
     */
    public static function checkAdmin(PDO $pdo): int {
        $uid = self::checkAPI();

        $stmtUser = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $stmtUser->execute([$uid]);
        $userRole = $stmtUser->fetchColumn();

        if ($userRole !== 'admin') {
            self::sendError(403, 'Acceso denegado. Se requiere rol de administrador.');
        }

        return $uid;
    }

    /**
     * Verifica el token CSRF para peticiones que modifican estado (POST).
     */
    public static function checkCSRF(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Intentar leer el token del cuerpo si la petición es JSON
            $inputData = json_decode(file_get_contents('php://input'), true);
            $jsonToken = is_array($inputData) ? ($inputData['csrf_token'] ?? '') : '';

            $csrfToken = trim((string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? $jsonToken));
            $sessionToken = trim((string)($_SESSION['csrf_token'] ?? ''));

            $isSameOrigin = false;
            if (isset($_SERVER['HTTP_SEC_FETCH_SITE']) && $_SERVER['HTTP_SEC_FETCH_SITE'] === 'same-origin') {
                $isSameOrigin = true;
            } elseif (isset($_SERVER['HTTP_REFERER']) && isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
                $isSameOrigin = true;
            }

            // Si el navegador NO puede confirmar que es nuestra propia web, exigimos el token estricto
            if (!$isSameOrigin) {
                if (empty($sessionToken)) {
                    self::sendError(401, 'Sesión expirada o cookies bloqueadas por el navegador.');
                }
                if (empty($csrfToken) || !hash_equals($sessionToken, $csrfToken)) {
                    self::sendError(403, 'Token de seguridad ausente o inválido. Refresca la página.');
                }
            }
        }
    }

    /**
     * Valida un array de datos contra un conjunto de reglas.
     * Si la validación falla, devuelve un error HTTP 400 automáticamente.
     * @param array|null $data Los datos de entrada (ej. json_decode).
     * @param array $rules Las reglas (ej. ['id' => 'required|numeric', 'email' => 'required|email']).
     * @param bool $isApi Si es true, devuelve JSON 400. Si es false, lanza Exception.
     * @return array Los datos validados y saneados.
     */
    public static function validateInput(?array $data, array $rules, bool $isApi = true): array {
        $validated = [];
        $errors = [];
        $data = $data ?: [];

        foreach ($rules as $field => $ruleString) {
            $ruleArray = explode('|', $ruleString);
            $value = isset($data[$field]) ? (is_string($data[$field]) ? trim($data[$field]) : $data[$field]) : null;

            foreach ($ruleArray as $rule) {
                if ($rule === 'required' && ($value === null || $value === '' || (is_array($value) && empty($value)))) {
                    $errors[] = "El campo '$field' es obligatorio.";
                }
                if ($value !== null && $value !== '') {
                    if ($rule === 'numeric' && !is_numeric($value)) {
                        $errors[] = "El campo '$field' debe ser un número.";
                    }
                    if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "El campo '$field' debe ser un email válido.";
                    }
                    if ($rule === 'array' && !is_array($value)) {
                        $errors[] = "El campo '$field' debe ser una lista válida.";
                    }
                    if ($rule === 'date' && !is_array($value)) {
                        // Valida formato y que la fecha exista realmente (ej. rechaza 2023-02-30)
                        $d = DateTime::createFromFormat('Y-m-d', (string)$value);
                        if (!$d || $d->format('Y-m-d') !== (string)$value) {
                            $errors[] = "El campo '$field' debe ser una fecha válida (YYYY-MM-DD).";
                        }
                    }
                    if (strpos($rule, 'max:') === 0) {
                        $maxLen = (int) substr($rule, 4); // Extraemos el número después de 'max:'
                        if (is_string($value) && mb_strlen($value, 'UTF-8') > $maxLen) {
                            $errors[] = "El campo '$field' no puede exceder los $maxLen caracteres.";
                        }
                    }
                    if (strpos($rule, 'min:') === 0) {
                        $minLen = (int) substr($rule, 4); // Extraemos el número después de 'min:'
                        if (is_string($value) && mb_strlen($value, 'UTF-8') < $minLen) {
                            $errors[] = "El campo '$field' debe tener al menos $minLen caracteres.";
                        }
                    }
                }
            }
            $validated[$field] = $value;
        }

        if (!empty($errors)) {
            $errorMsg = implode(' ', $errors);
            if ($isApi) {
                self::sendError(400, $errorMsg);
            } else {
                throw new Exception($errorMsg);
            }
        }
        return $validated;
    }

    private static function sendError(int $code, string $message): void {
        // Limpiar cualquier basura en el buffer para no romper el formato JSON
        if (ob_get_length()) {
            ob_end_clean();
        }
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
