
<?php
class Security {
    private static $csrfTokens = [];
    
    public static function validateSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'cookie_samesite' => 'Strict'
            ]);
        }
        
        // Check session hijacking
        if (isset($_SESSION['user_ip']) && $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
            session_destroy();
            header("Location: login.php?error=session_invalid");
            exit();
        }
        
        if (!isset($_SESSION['student_id']) || empty($_SESSION['student_id'])) {
            header("Location: login.php");
            exit();
        }
        
        // Set user IP if not set
        if (!isset($_SESSION['user_ip'])) {
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
        }
        
        return (int)$_SESSION['student_id'];
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateStudentId($student_id) {
        if (!is_numeric($student_id) || $student_id <= 0) {
            throw new InvalidArgumentException("Invalid student ID");
        }
        
        return (int)$student_id;
    }
    
    public static function setSecurityHeaders() {
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        header("Content-Security-Policy: default-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;");
    }
    
    public static function generateCSRFToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        self::$csrfTokens[] = $token;
        
        return $token;
    }
    
    public static function validateCSRFToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function rateLimitCheck($key, $max_attempts = 5, $time_window = 900) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $rate_key = "rate_limit_" . $key;
        
        if (!isset($_SESSION[$rate_key])) {
            $_SESSION[$rate_key] = ['count' => 0, 'last_attempt' => time()];
        }
        
        $rate_data = $_SESSION[$rate_key];
        $current_time = time();
        
        // Reset counter if time window has passed
        if ($current_time - $rate_data['last_attempt'] > $time_window) {
            $_SESSION[$rate_key] = ['count' => 0, 'last_attempt' => $current_time];
            return true;
        }
        
        // Check if rate limit exceeded
        if ($rate_data['count'] >= $max_attempts) {
            return false;
        }
        
        // Increment counter
        $_SESSION[$rate_key]['count']++;
        $_SESSION[$rate_key]['last_attempt'] = $current_time;
        
        return true;
    }
}
?>
