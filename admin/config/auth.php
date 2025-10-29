<?php

/**
 * Authentication & Authorization Class
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 * 
 * Handles user authentication, authorization, session management,
 * and security features like CSRF protection
 */

declare(strict_types=1);

require_once __DIR__ . '/database.php';

class Auth
{
    private Database $db;
    private string $sessionName = 'sarana_admin_session';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->startSession();
    }

    /**
     * Start secure session
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.use_strict_mode', '1');

            session_name($this->sessionName);
            session_start();

            // Regenerate session ID periodically for security
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } elseif (time() - $_SESSION['created'] > 3600) { // 1 hour
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }

    /**
     * Authenticate user with username and password
     */
    public function login(string $username, string $password): bool
    {
        try {
            // Rate limiting check (simple implementation)
            $this->checkLoginAttempts($username);

            $sql = "SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1";
            $user = $this->db->fetchOne($sql, [$username]);

            if ($user && password_verify($password, $user['password'])) {
                // Clear failed attempts on successful login
                unset($_SESSION['login_attempts'][$username]);

                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();

                // Generate CSRF token
                $this->generateCSRFToken();

                // Log successful login
                $this->logActivity('login', "User {$username} logged in successfully");

                return true;
            } else {
                // Record failed attempt
                $this->recordFailedAttempt($username);

                // Log failed login attempt
                $this->logActivity('failed_login', "Failed login attempt for username: {$username}");

                return false;
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is authenticated
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) &&
            $_SESSION['logged_in'] === true &&
            isset($_SESSION['user_id']);
    }

    /**
     * Get current user data
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        try {
            $sql = "SELECT id, username, role, created_at FROM users WHERE id = ? LIMIT 1";
            return $this->db->fetchOne($sql, [$_SESSION['user_id']]);
        } catch (Exception $e) {
            error_log("Get current user error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Logout user and destroy session
     */
    public function logout(): bool
    {
        try {
            $username = $_SESSION['username'] ?? 'Unknown';

            // Log logout activity
            $this->logActivity('logout', "User {$username} logged out");

            // Clear session data
            $_SESSION = [];

            // Delete session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }

            // Destroy session
            session_destroy();

            return true;
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Require user to be authenticated
     */
    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirectToLogin();
        }
    }

    /**
     * Require specific role
     */
    public function requireRole(string $role): void
    {
        $this->requireAuth();

        if (!$this->hasRole($role)) {
            http_response_code(403);
            $this->logActivity('access_denied', "Access denied for role: {$_SESSION['user_role']} to {$role}");
            die('Access denied. Insufficient permissions.');
        }
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $userRole = $_SESSION['user_role'] ?? '';

        if ($userRole === 'admin') {
            return true;
        }

        return $userRole === $role;
    }

    /**
     * Generate CSRF token
     */
    public function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) &&
            hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get CSRF token for forms
     */
    public function getCSRFToken(): string
    {
        return $_SESSION['csrf_token'] ?? $this->generateCSRFToken();
    }

    /**
     * Redirect to login page
     */
    private function redirectToLogin(): void
    {
        $loginUrl = '../admin/login.php';

        if (!isset($_SESSION['intended_url']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        }

        header("Location: {$loginUrl}");
        exit;
    }

    /**
     * Get intended URL after login
     */
    public function getIntendedUrl(): string
    {
        $url = $_SESSION['intended_url'] ?? '/admin/index.php';
        unset($_SESSION['intended_url']);
        return $url;
    }

    /**
     * Check and limit login attempts
     */
    private function checkLoginAttempts(string $username): void
    {
        $maxAttempts = 5;
        $lockoutTime = 900; 

        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }

        $attempts = $_SESSION['login_attempts'][$username] ?? [];
        $recentAttempts = array_filter($attempts, function ($timestamp) use ($lockoutTime) {
            return (time() - $timestamp) < $lockoutTime;
        });

        if (count($recentAttempts) >= $maxAttempts) {
            $this->logActivity('account_locked', "Account locked for username: {$username}");
            throw new Exception("Too many failed login attempts. Please try again later.");
        }
    }

    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt(string $username): void
    {
        if (!isset($_SESSION['login_attempts'][$username])) {
            $_SESSION['login_attempts'][$username] = [];
        }

        $_SESSION['login_attempts'][$username][] = time();
    }

    /**
     * Log user activity
     */
    private function logActivity(string $action, string $description): void
    {
        try {
            $logData = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? 'Anonymous',
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $logEntry = json_encode($logData) . "\n";
            file_put_contents(__DIR__ . '/../logs/admin_activity.log', $logEntry, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
        }
    }

    /**
     * Hash password for storage
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, 
            'time_cost' => 4,       
            'threads' => 3,         
        ]);
    }

    /**
     * Validate password strength
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        return $errors;
    }
}

// Helper functions for easy access
function requireAuth(): void
{
    $auth = new Auth();
    $auth->requireAuth();
}

function requireRole(string $role): void
{
    $auth = new Auth();
    $auth->requireRole($role);
}

function getCurrentUser(): ?array
{
    $auth = new Auth();
    return $auth->getCurrentUser();
}

function getCSRFToken(): string
{
    $auth = new Auth();
    return $auth->getCSRFToken();
}

function verifyCSRFToken(string $token): bool
{
    $auth = new Auth();
    return $auth->verifyCSRFToken($token);
}
