<?php
// =====================================================
// setup_admin.php — One-Time Admin Account Creator
// =====================================================
// Run this ONCE in your browser after importing schema.sql:
//   http://localhost/healthcare/setup_admin.php
// 
// It creates the admin account:
//   Email:    admin@healthcare.com
//   Password: admin123
//
// ⚠️ DELETE THIS FILE after running it for security!
// =====================================================

require 'db.php';

// Admin account details
$admin_name  = "System Administrator";
$admin_email = "admin@healthcare.com";
$admin_pass  = "admin123";
$admin_phone = "03001234567";

// Check if admin already exists
$check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
$check->bind_param("s", $admin_email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<h2>⚠️ Admin account already exists!</h2>";
    echo "<p>Email: $admin_email</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
} else {
    // Hash the password using bcrypt
    $hashed = password_hash($admin_pass, PASSWORD_DEFAULT);

    // Insert admin user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'admin', ?)");
    $stmt->bind_param("ssss", $admin_name, $admin_email, $hashed, $admin_phone);

    if ($stmt->execute()) {
        echo "<h2 style='color: green;'>✅ Admin account created successfully!</h2>";
        echo "<p><strong>Email:</strong> $admin_email</p>";
        echo "<p><strong>Password:</strong> $admin_pass</p>";
        echo "<p style='color: red;'><strong>⚠️ DELETE this file (setup_admin.php) now for security!</strong></p>";
        echo "<p><a href='login.php'>Go to Login →</a></p>";
    } else {
        echo "<h2 style='color: red;'>❌ Failed to create admin account.</h2>";
        echo "<p>Error: " . htmlspecialchars($stmt->error) . "</p>";
    }
    $stmt->close();
}

$check->close();
$conn->close();
?>
