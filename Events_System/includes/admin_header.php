<?php
if (!isset($_SESSION['admin_id'])) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CVSU CEMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="nav-brand">CVSU <span>Admin</span></a>
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="participants.php">Participants</a></li>
                <li><a href="feedbacks.php">Feedbacks</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="../index.php" class="btn btn-ghost btn-sm">Main Site</a></li>
                <li><a href="logout.php" class="btn btn-outline btn-sm">Logout</a></li>
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
    <div class="container">
