<?php
require_once '../includes/config.php';

if (isset($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $data = [
            'full_name' => $full_name,
            'email' => $email,
            'username' => $username,
            'password' => $hashed,
            'role' => 'organizer'
        ];
        $result = $supabase->insert('admins', $data);
        if ($result['error']) {
            $error = 'Registration failed: Username or email may already exist.';
        } else {
            setFlash('success', 'Registration successful. Please login.');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register - CVSU CEMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card" style="max-width: 500px;">
            <div class="logo">CVSU</div>
            <h2>Admin Registration</h2>
            <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="register.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-green btn-lg" style="width: 100%;">Register</button>
                </div>
                <p style="text-align: center; font-size: 0.9rem; color: var(--medium-text); margin-top: 16px;">
                    Already have an account? <a href="login.php" style="color: var(--primary-green); font-weight: 600;">Login</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
