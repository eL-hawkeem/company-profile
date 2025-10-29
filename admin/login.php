<?php

/**
 * Admin Login Page
 * PT. Sarana Sentra Teknologi Utama - Admin Dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!$auth->verifyCSRFToken($csrf_token)) {
            throw new Exception('Security token mismatch. Please try again.');
        }

        // Get and validate input
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            throw new Exception('Please enter both username and password.');
        }

        // Attempt login
        if ($auth->login($username, $password)) {
            // Redirect to intended page or dashboard
            $redirectUrl = $auth->getIntendedUrl();
            header("Location: {$redirectUrl}");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= APP_NAME ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            background: linear-gradient(135deg, #6f42c1 0%, #20c997 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            background: linear-gradient(135deg, #6f42c1 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }

        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .login-body {
            padding: 2rem;
        }

        .form-floating {
            margin-bottom: 1rem;
            position: relative;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 0;
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #6f42c1;
        }

        .password-toggle:focus {
            outline: none;
            color: #6f42c1;
        }

        .btn-login {
            background: linear-gradient(135deg, #6f42c1 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.4);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .login-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .loading {
            display: none;
        }

        .loading.show {
            display: inline-block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card {
            animation: fadeIn 0.6s ease-out;
        }

        @media (max-width: 576px) {
            .login-header {
                padding: 1.5rem;
            }

            .login-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-shield-alt me-2"></i>Admin Login</h2>
                <p>PT. Sarana Sentra Teknologi Utama</p>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $auth->getCSRFToken() ?>">

                    <div class="form-floating">
                        <input type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            placeholder="Username"
                            required
                            autocomplete="username">
                        <label for="username">
                            <i class="fas fa-user me-2"></i>Username
                        </label>
                        <div class="invalid-feedback">
                            Please enter your username.
                        </div>
                    </div>

                    <div class="form-floating">
                        <input type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Password"
                            required
                            autocomplete="current-password">
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>Password
                        </label>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                        <div class="invalid-feedback">
                            Please enter your password.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-login w-100 mt-3">
                        <span class="login-text">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </span>
                        <span class="loading">
                            <i class="fas fa-spinner fa-spin me-2"></i>Signing in...
                        </span>
                    </button>
                </form>
            </div>

            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> PT. Sarana Sentra Teknologi Utama. All rights reserved.</p>
                <p><small>Version <?= APP_VERSION ?></small></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });

        // Form validation and submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const form = this;
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            const loginText = document.querySelector('.login-text');
            const loading = document.querySelector('.loading');
            const submitBtn = form.querySelector('button[type="submit"]');

            // Reset previous validation states
            form.classList.remove('was-validated');
            username.classList.remove('is-invalid');
            password.classList.remove('is-invalid');

            let isValid = true;

            // Validate username
            if (!username.value.trim()) {
                username.classList.add('is-invalid');
                isValid = false;
            }

            // Validate password
            if (!password.value) {
                password.classList.add('is-invalid');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                form.classList.add('was-validated');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            loginText.style.display = 'none';
            loading.classList.add('show');

            // Form will submit normally after this
        });

        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            const username = document.getElementById('username');
            username.focus();
        });

        // Handle Enter key in form fields
        document.querySelectorAll('#loginForm input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('loginForm').requestSubmit();
                }
            });
        });

        // Remove invalid class on input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });

        // Security: Clear form data on page unload
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });

        // Prevent form resubmission on back button
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>