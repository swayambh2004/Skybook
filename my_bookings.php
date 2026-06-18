<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    die("Login required");
}
$user_id = (int)$_SESSION['user_id'];

/* Fetch bookings with flight details */
$stmt = $conn->prepare("
    SELECT b.booking_id, b.total_amount, b.booking_date,
           f.flight_name, f.source, f.destination,
           f.departure_time, f.arrival_time
    FROM bookings b
    JOIN flights f ON b.flight_id = f.flight_id
    WHERE b.user_id = ?
    ORDER BY f.departure_time DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings | SkyBook</title>
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

        .page-header {
            background: linear-gradient(135deg, var(--primary-blue), #0056b3);
            padding: 3rem 0;
            color: white;
            margin-bottom: 2rem;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
        }

        .booking-card {
            background: var(--white);
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 53, 128, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            overflow: hidden;
            border-left: 6px solid var(--primary-blue);
        }
        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 53, 128, 0.1);
        }

        .flight-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2rem;
        }

        .city-code {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-blue);
            margin-bottom: 0;
            line-height: 1;
        }
        .city-name {
            font-size: 0.9rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 500;
        }

        .flight-path {
            flex-grow: 1;
            text-align: center;
            position: relative;
            padding: 0 3rem;
        }
        .flight-path::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 15%;
            right: 15%;
            border-top: 2px dashed #cbd5e1;
            z-index: 1;
        }
        .plane-icon {
            background: var(--white);
            position: relative;
            z-index: 2;
            padding: 0 15px;
            color: var(--action-orange);
            font-size: 1.4rem;
        }

        .booking-meta {
            background: #f8fafc;
            padding: 1.2rem 2rem;
            border-top: 1px solid #edf2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .btn-view { 
            background: var(--action-orange);
            color: white;
            border: none;
            border-radius: 50px; 
            font-weight: 600; 
            padding: 0.5rem 1.5rem;
            transition: 0.3s;
        }
        .btn-view:hover {
            background: #e67e00;
            color: white;
            box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3);
        }

        .btn-cancel { 
            font-size: 0.85rem;
            font-weight: 600; 
            color: #d63031; 
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-cancel:hover { color: #a82323; }

        .price-text {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-paper-plane"></i> SKY<span>BOOK</span></a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="index.php">Book New Flight</a>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h2 class="fw-bold mb-1">Your Journey History</h2>
        <p class="opacity-75 mb-0">Manage and track all your SkyBook reservations</p>
    </div>
</div>

<div class="container pb-5">

    <?php if ($result->num_rows === 0): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="fas fa-plane-arrival fa-4x text-muted mb-3"></i>
            <h4 class="fw-bold">No Adventures Yet</h4>
            <p class="text-muted">You haven't booked any flights with us yet.</p>
            <a href="index.php" class="btn btn-view mt-2">Find a Flight</a>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-11 mx-auto">
            
            <?php while ($row = $result->fetch_assoc()): 
                $isUpcoming = strtotime($row['departure_time']) > time();
            ?>
            <div class="booking-card animate__animated animate__fadeIn">
                <div class="flight-info">
                    <div class="text-start">
                        <p class="city-code"><?= htmlspecialchars(strtoupper(substr($row['source'], 0, 3))) ?></p>
                        <p class="city-name"><?= htmlspecialchars($row['source']) ?></p>
                        <div class="mt-2">
                            <i class="far fa-clock text-primary me-1"></i>
                            <span class="fw-bold small"><?= date('H:i', strtotime($row['departure_time'])) ?></span>
                        </div>
                    </div>

                    <div class="flight-path">
                        <span class="small d-block text-muted mb-2 fw-bold"><?= htmlspecialchars($row['flight_name']) ?></span>
                        <span class="plane-icon"><i class="fas fa-plane"></i></span>
                        <span class="small d-block text-muted mt-2 fw-bold"><?= date('D, d M Y', strtotime($row['departure_time'])) ?></span>
                    </div>

                    <div class="text-end">
                        <p class="city-code"><?= htmlspecialchars(strtoupper(substr($row['destination'], 0, 3))) ?></p>
                        <p class="city-name"><?= htmlspecialchars($row['destination']) ?></p>
                        <div class="mt-2">
                            <i class="far fa-clock text-primary me-1"></i>
                            <span class="fw-bold small"><?= date('H:i', strtotime($row['arrival_time'])) ?></span>
                        </div>
                    </div>
                </div>

                <div class="booking-meta">
                    <div class="d-flex align-items-center">
                        <span class="status-badge <?= $isUpcoming ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                            <i class="fas fa-circle me-1" style="font-size: 6px;"></i> <?= $isUpcoming ? 'UPCOMING' : 'PAST TRIP' ?>
                        </span>
                        <span class="ms-3 text-muted small">Ref: <strong>#<?= (int)$row['booking_id'] ?></strong></span>
                    </div>
                    
                    <div class="d-flex gap-4 align-items-center">
                        <span class="price-text">Rs. <?= number_format($row['total_amount'], 2) ?></span>
                        
                        <a href="view_ticket.php?booking_id=<?= (int)$row['booking_id'] ?>" class="btn btn-view">
                            View Ticket
                        </a>

                        <?php if ($isUpcoming): ?>
                        <form method="POST" action="cancel_booking.php" onsubmit="return confirm('Cancel this flight reservation?');">
                            <input type="hidden" name="booking_id" value="<?= (int)$row['booking_id'] ?>">
                            <button class="btn btn-cancel border-0 p-0">
                                <i class="far fa-times-circle me-1"></i> Cancel
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

        </div>
    </div>
</div>

<footer class="footer text-center text-white py-4" style="background: var(--primary-blue); margin-top: 50px;">
    <div class="container">
        <small>© 2026 SkyBook Global Systems</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>