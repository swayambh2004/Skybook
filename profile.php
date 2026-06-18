<?php
session_start();
include "db.php";

/* Ensure user is logged in */
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}

$user_id = (int)$_SESSION['user_id'];

/* Fetch user data */
$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile | SkyBook</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-blue: #003580;
            --action-orange: #ff8c00;
            --bg-light: #f0f4f8;
            --white: #ffffff;
            --text-dark: #002244;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            margin: 0;
        }

        /* --- NAVIGATION --- */
        .navbar {
            background: var(--white) !important;
            padding: 15px 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            border-bottom: 3px solid var(--primary-blue);
        }
        .navbar-brand { font-weight: 700; color: var(--primary-blue) !important; }
        .navbar-brand i { color: var(--action-orange); }

        /* --- PROFILE HEADER --- */
        .profile-header {
            background: linear-gradient(135deg, var(--primary-blue), #0056b3);
            height: 200px;
            width: 100%;
            position: relative;
            clip-path: polygon(0 0, 100% 0, 100% 80%, 0 100%);
        }

        .profile-container {
            max-width: 500px;
            margin: -100px auto 50px;
            position: relative;
            z-index: 10;
            padding: 0 20px;
        }

        .profile-card {
            background: var(--white);
            border-radius: 25px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 53, 128, 0.1);
            border-top: 5px solid var(--action-orange);
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            background: var(--primary-blue);
            color: white;
            font-size: 42px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 20px;
            border: 5px solid var(--white);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .user-name { font-size: 1.5rem; font-weight: 700; color: var(--primary-blue); margin-bottom: 5px; }
        .user-role { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: 600; margin-bottom: 25px; }

        .info-row {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 12px;
            text-align: left;
        }
        .info-row i { color: var(--action-orange); width: 40px; font-size: 1.1rem; }
        .info-label { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; display: block; }
        .info-value { font-size: 0.95rem; color: var(--text-dark); font-weight: 500; }

        .btn-home {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: 0.3s;
        }
        .btn-home:hover { background: #0044a3; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0, 53, 128, 0.2); color: white; }

        .btn-logout {
            color: #d63031;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            display: block;
            margin-top: 20px;
        }
        .btn-logout:hover { color: #a82323; }

    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-paper-plane"></i> SKY<span>BOOK</span></a>
    </div>
</nav>

<div class="profile-header"></div>

<div class="profile-container text-center">
    <div class="profile-card">
        <div class="avatar-circle">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>

        <h3 class="user-name"><?= htmlspecialchars($user['name']) ?></h3>
        <p class="user-role"><?= htmlspecialchars($user['role']) ?></p>

        <div class="info-row">
            <i class="fas fa-envelope"></i>
            <div>
                <span class="info-label">Email Address</span>
                <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
            </div>
        </div>

        <div class="info-row">
            <i class="fas fa-shield-alt"></i>
            <div>
                <span class="info-label">Account Status</span>
                <span class="info-value text-success">Verified Passenger</span>
            </div>
        </div>

        <button class="btn-home" onclick="location.href='index.php'">
            <i class="fas fa-home me-2"></i> Return Home
        </button>

        <a href="logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt me-1"></i> Sign Out
        </a>
    </div>
</div>

<footer class="text-center py-4 opacity-50 small">
    © 2026 SkyBook Global Passenger Systems
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>