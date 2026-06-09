<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CVSU CEMS - Campus Event Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="nav-brand">CVSU <span>CEMS</span></a>
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="admin/login.php" class="btn btn-outline btn-sm">Admin Login</a></li>
            </ul>
        </div>
    </nav>
    <?php if ($msg = getFlash('success')): ?>
    <div class="flash-container"><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div></div>
    <?php endif; ?>
    <?php if ($msg = getFlash('error')): ?>
    <div class="flash-container"><div class="alert alert-error"><?php echo htmlspecialchars($msg); ?></div></div>
    <?php endif; ?>
    <main>
