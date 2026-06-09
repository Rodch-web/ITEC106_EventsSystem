<?php
require_once '../includes/config.php';

if (isset($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $supabase->select('admins', '*', ['username' => 'eq.' . $username], null, 1);
    $admin = $result['data'][0] ?? null;

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        redirect('dashboard.php');
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CVSU CEMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">CVSU</div>
            <h2>Admin Login</h2>
            <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-green btn-lg" style="width: 100%;">Login</button>
                </div>
                <p style="text-align: center; font-size: 0.9rem; color: var(--medium-text); margin-top: 16px;">
                    Don't have an account? <a href="register.php" style="color: var(--primary-green); font-weight: 600;">Register</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
