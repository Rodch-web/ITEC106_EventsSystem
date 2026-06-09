<?php
session_start();

require_once __DIR__ . '/json_db.php';

// JSON file storage (no database / XAMPP required)
$data_dir = __DIR__ . '/../data';
$supabase = new JsonDataClient($data_dir);

// Helper functions
function setFlash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function getFlash($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function redirect($url) {
    header("Location: " . $url);
    exit;
}

function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        setFlash('error', 'Please login to access the admin panel.');
        redirect('login.php');
    }
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}

function generateReference() {
    return 'CEMS-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
}

function uploadImage($file) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.'];
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['error' => 'File too large. Max 2MB.'];
    }
    $filename = 'event_' . time() . '_' . uniqid() . '.' . $ext;
    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['success' => 'assets/uploads/' . $filename];
    }
    return ['error' => 'Failed to upload file.'];
}

function slugify($text) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
}
