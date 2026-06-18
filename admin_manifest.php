<?php
session_start();
include "db.php";

/* 🔐 1. SESSION SECURITY: Strict Admin Role Enforcement */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get the Flight ID from the URL
if (!isset($_GET['flight_id'])) {
    die("Error: Flight ID not specified.");
}

$flight_id = (int)$_GET['flight_id'];

// 2. Fetch Flight Details for the Header
$f_stmt = $conn->prepare("SELECT * FROM flights WHERE flight_id = ?");
$f_stmt->bind_param("i", $flight_id);
$f_stmt->execute();
$flight = $f_stmt->get_result()->fetch_assoc();

if (!$flight) {
    die("Error: Flight record not found.");
}

// 3. Fetch Passengers using your specific column names
// Joined with users table to get the contact email of the person who booked
$m_sql = "SELECT bs.passenger_name, bs.passenger_age, bs.seat, bs.seat_class, bs.meal_preference, u.email 
          FROM booking_seats bs
          JOIN users u ON bs.user_id = u.user_id 
          WHERE bs.flight_id = ? 
          ORDER BY bs.seat ASC";

$m_stmt = $conn->prepare($m_sql);
$m_stmt->bind_param("i", $flight_id);
$m_stmt->execute();
$passengers = $m_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flight Manifest | <?= htmlspecialchars($flight['flight_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; color: #002244; }
        .manifest-container { 
            background: white; 
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
            margin-top: 20px;
        }
        .header-line { 
            border-left: 6px solid #ff8c00; 
            padding-left: 20px; 
            margin-bottom: 40px; 
        }
        .table thead th { 
            background: #003580; 
            color: white; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            letter-spacing: 1px;
            padding: 15px;
            border: none;
        }
        .table tbody td { padding: 15px; vertical-align: middle; }
        .seat-badge { background: #eef4ff; color: #003580; font-weight: 700; padding: 5px 10px; border-radius: 6px; }
        
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .manifest-container { box-shadow: none; border: 1px solid #eee; margin: 0; padding: 20px; }
            .header-line { border-left-color: #333; }
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <a href="admin_flights.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fas fa-arrow-left me-2"></i>Back to Inventory
        </a>
        <button class="btn btn-dark rounded-pill px-4" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Print Passenger Manifest
        </button>
    </div>

    <div class="manifest-container">
        <div class="header-line">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="fw-bold mb-1"><?= htmlspecialchars($flight['flight_name']) ?></h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-plane-departure me-2"></i>
                        <?= htmlspecialchars($flight['source']) ?> <i class="fas fa-long-arrow-alt-right mx-2"></i> <?= htmlspecialchars($flight['destination']) ?>
                    </p>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark border p-2">
                        FLIGHT DATE: <?= date('d M Y', strtotime($flight['departure_time'])) ?>
                    </span><br>
                    <small class="text-muted">Manifest Generated: <?= date('d M Y, H:i') ?></small>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Seat</th>
                        <th>Passenger Name</th>
                        <th>Age</th>
                        <th>Class</th>
                        <th>Meal Preference</th>
                        <th>Account Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($passengers->num_rows > 0): ?>
                        <?php while($p = $passengers->fetch_assoc()): ?>
                        <tr>
                            <td><span class="seat-badge"><?= htmlspecialchars($p['seat']) ?></span></td>
                            <td><div class="fw-bold"><?= htmlspecialchars($p['passenger_name']) ?></div></td>
                            <td><?= htmlspecialchars($p['passenger_age']) ?> yrs</td>
                            <td>
                                <span class="badge <?= strtolower($p['seat_class']) == 'business' ? 'bg-warning text-dark' : 'bg-secondary' ?> text-uppercase">
                                    <?= htmlspecialchars($p['seat_class']) ?>
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-utensils me-2 text-warning opacity-75"></i>
                                <?= htmlspecialchars($p['meal_preference']) ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($p['email']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-users-slash fa-3x mb-3 opacity-25"></i>
                                <p>No passengers found in the manifest for this flight.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if($passengers->num_rows > 0): ?>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end fw-bold">Total Confirmed Passengers:</td>
                        <td class="fw-bold text-dark"><?= $passengers->num_rows ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <div class="mt-5 pt-4 border-top d-none d-print-block">
            <div class="row">
                <div class="col-6">
                    <small class="text-muted">Verified by (Station Manager): __________________________</small><br>
                    <small class="text-muted">Timestamp: <?= date('d M Y, H:i') ?></small>
                </div>
                <div class="col-6 text-end">
                    <small class="text-muted">Document Hash: #SKY-<?= $flight_id ?>-<?= time() ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>