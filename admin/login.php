<?php
session_start();
require_once '../includes/config.php';

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin') {
    header('Location: dashboard.php');
    exit();
}

// Handle login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        // Check admin credentials
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Login successful
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['nama'] = $admin['nama'];

            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Email atau password salah!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sahabat Tani</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #2c2c2c 0%, #4f4f4f 50%, #e0e0e0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        /* Subtle animated background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(0, 0, 0, 0.1) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow:
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            max-width: 420px;
            margin: 0 auto;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow:
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.3);
        }

        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
            position: relative;
        }

        .admin-icon {
            background: rgba(255, 255, 255, 0.15);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            transition: all 0.3s ease;
        }

        .admin-icon:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
        }

        .login-body {
            padding: 2rem 1.5rem;
        }

        .form-control {
            background: rgba(248, 249, 250, 0.8);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 0.875rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            color: #2c3e50;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 1);
            border-color: #6c757d;
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.15);
            transform: translateY(-1px);
        }

        .form-control::placeholder {
            color: #6c757d;
            opacity: 0.7;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-admin {
            background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
            border: none;
            padding: 0.875rem 1.5rem;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-admin:hover {
            background: linear-gradient(135deg, #343a40 0%, #495057 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            background: rgba(220, 53, 69, 0.1);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .back-link {
            color: #6c757d;
            text-decoration: none;
            font-weight: 400;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
        }

        .back-link:hover {
            color: #495057;
            background: rgba(108, 117, 125, 0.1);
            transform: translateX(-3px);
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 0.85rem;
            opacity: 0.8;
            font-weight: 400;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-card {
                margin: 1rem;
                max-width: none;
            }

            .login-body {
                padding: 1.5rem 1.25rem;
            }

            .login-header {
                padding: 1.5rem 1.25rem;
            }

            .admin-icon {
                width: 60px;
                height: 60px;
            }

            .logo-text {
                font-size: 1.25rem;
            }
        }

        /* Subtle animations */
        .login-card {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-control,
        .btn-admin {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-6">
                <div class="login-card">
                    <div class="login-header">
                        <div class="admin-icon">
                            <i class="fas fa-user-shield fa-lg"></i>
                        </div>
                        <h1 class="logo-text">Admin Panel</h1>
                        <p class="subtitle mb-0">Sahabat Tani Management</p>
                    </div>

                    <div class="login-body">
                        <?php if (isset($error)): ?>
                            <div class="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <input type="email" class="form-control"
                                    name="email" required
                                    placeholder="admin@sahabattani.com"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" class="form-control"
                                    name="password" required
                                    placeholder="••••••••">
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-admin">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Sign In
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <a href="../index.php" class="back-link">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

