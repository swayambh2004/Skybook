<?php
session_start();
include "db.php";

/* 🔐 1. SESSION SECURITY: Strict Admin Role Enforcement */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

/* 📊 2. FETCH DASHBOARD DATA */
$flight_res = $conn->query("SELECT COUNT(*) as total FROM flights");
$flight_count = $flight_res->fetch_assoc()['total'];

$rev_res = $conn->query("SELECT SUM(total_amount) as total FROM bookings");
$revenue = $rev_res->fetch_assoc()['total'] ?? 0;

$user_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$user_count = $user_res->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SkyBook Operations | Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-blue: #003580;
            --action-orange: #ff8c00;
            --sidebar-dark: #002244;
            --bg-light: #f0f4f8;
            --white: #ffffff;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-light); 
            margin: 0;
            color: #1e293b;
        }

        /* --- SIDEBAR --- */
        .sidebar { 
            width: 280px; 
            height: 100vh; 
            position: fixed; 
            background: var(--sidebar-dark); 
            color: white; 
            padding: 30px 15px;
            z-index: 1000;
        }
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
            letter-spacing: 1px;
        }
        .sidebar-brand span { color: var(--action-orange); }

        .nav-link { 
            color: rgba(255,255,255,0.7) !important; 
            padding: 14px 20px; 
            border-radius: 12px; 
            transition: 0.3s; 
            font-weight: 500;
            margin-bottom: 8px;
        }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: white !important; }
        .nav-link.active { background: var(--action-orange); color: white !important; box-shadow: 0 4px 15px rgba(255,140,0,0.3); }
        .nav-link i { width: 25px; font-size: 1.1rem; }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: 280px; padding: 50px; }
        
        /* --- STAT CARDS --- */
        .stat-card { 
            border: none; 
            border-radius: 20px; 
            padding: 30px; 
            background: var(--white); 
            box-shadow: 0 10px 30px rgba(0,53,128,0.05);
            transition: transform 0.3s;
            border-left: 6px solid #ddd;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .card-flights { border-left-color: var(--primary-blue); }
        .card-revenue { border-left-color: var(--action-orange); }
        .card-users { border-left-color: #10b981; }

        .label-text {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* --- QUICK ACTIONS --- */
        .action-card {
            background: var(--white);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #edf2f7;
        }
        .btn-primary-custom {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-primary-custom:hover { background: #002a66; transform: translateY(-2px); color: white; }

        .btn-outline-custom {
            border: 2px solid #e2e8f0;
            background: transparent;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            color: #64748b;
            transition: 0.3s;
        }
        .btn-outline-custom:hover { background: #f8fafc; border-color: #cbd5e1; }

        @media print { .sidebar { display: none; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-paper-plane me-2"></i>SKY<span>BOOK</span>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link active" href="admin_dashboard.php">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a class="nav-link" href="admin_flights.php">
            <i class="fas fa-plane-departure"></i> Manage Flights
        </a>
        
        <div class="mt-5 pt-5 px-3">
            <a class="nav-link text-danger border border-danger border-opacity-25" href="alogout.php">
                <i class="fas fa-power-off"></i> Logout
            </a>
        </div>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold m-0">System Overview</h2>
            <p class="text-muted">Real-time operational metrics for SkyBook Global.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-white text-dark border p-2 rounded-pill">
                <i class="fas fa-clock text-primary me-2"></i>Server Time: <?= date('H:i') ?>
            </span>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card card-flights">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="label-text">Active Flights</h6>
                        <h2 class="fw-bold mt-2 m-0"><?= $flight_count ?></h2>
                    </div>
                    <div class="text-primary opacity-25">
                        <i class="fas fa-plane fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card card-revenue">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="label-text">Total Revenue</h6>
                        <h2 class="fw-bold mt-2 m-0 text-success">Rs. <?= number_format($revenue) ?></h2>
                    </div>
                    <div class="text-orange opacity-25" style="color: var(--action-orange)">
                        <i class="fas fa-wallet fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card card-users">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="label-text">Registered Users</h6>
                        <h2 class="fw-bold mt-2 m-0"><?= $user_count ?></h2>
                    </div>
                    <div class="text-success opacity-25">
                        <i class="fas fa-user-check fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="action-card">
        <h5 class="fw-bold mb-4"><i class="fas fa-bolt text-warning me-2"></i> Management Console</h5>
        <div class="row g-3">
            <div class="col-auto">
                <a href="admin_flights.php" class="btn btn-primary-custom">
                    <i class="fas fa-plus-circle me-2"></i>Deploy New Flight
                </a>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-custom" onclick="window.print()">
                    <i class="fas fa-file-invoice me-2"></i>Export Operations Report
                </button>
            </div>
            <div class="col-auto">
                <a href="index.php" class="btn btn-outline-custom">
                    <i class="fas fa-external-link-alt me-2"></i>Live Site Preview
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>