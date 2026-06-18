<?php
session_start();
include "db.php";

/* Check if user is logged in */
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to book a flight.");
}
$user_id = (int)$_SESSION['user_id'];

/* Validate required parameters */
if (!isset($_GET['source'], $_GET['destination'])) {
    die("Invalid request");
}

$source = trim($_GET['source']);
$destination = trim($_GET['destination']);

/* Optional date filter */
$travel_date = isset($_GET['travel_date']) && $_GET['travel_date'] !== ''
    ? $_GET['travel_date']
    : null;

/* ❌ Prevent booking for past dates (SERVER SIDE CHECK) */
if ($travel_date && strtotime($travel_date) < strtotime(date('Y-m-d'))) {
    die("You cannot book a flight for a past date.");
}

/* Prepare SQL based on date filter */
if ($travel_date) {
    $stmt = $conn->prepare("
        SELECT flight_id, flight_name, source, destination,
                departure_time, arrival_time, price, available_seats
        FROM flights
        WHERE source = ?
          AND destination = ?
          AND DATE(departure_time) = ?
          AND available_seats > 0
    ");
    $stmt->bind_param("sss", $source, $destination, $travel_date);
} else {
    // 🛡️ SECURITY FIX: Enforce that all fetched flights without explicitly filtered criteria must be in the future!
    $stmt = $conn->prepare("
        SELECT flight_id, flight_name, source, destination,
                departure_time, arrival_time, price, available_seats
        FROM flights
        WHERE source = ?
          AND destination = ?
          AND departure_time >= NOW()
          AND available_seats > 0
        ORDER BY departure_time ASC
    ");
    $stmt->bind_param("ss", $source, $destination);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Flights | SkyBook</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .navbar-brand { 
            font-weight: 700; 
            color: var(--primary-blue) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-brand i { color: var(--action-orange); }

        /* --- MODIFY SEARCH SECTION --- */
        .modify-box {
            background: var(--white);
            border-radius: 15px;
            border-left: 5px solid var(--primary-blue);
        }

        /* --- FLIGHT CARDS --- */
        .flight-card { 
            border: none; 
            border-radius: 20px; 
            background: var(--white);
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .flight-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 15px 30px rgba(0,53,128,0.12) !important; 
        }

        .flight-name {
            color: var(--primary-blue);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .route-path {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--primary-blue);
        }

        .plane-divider {
            flex-grow: 1;
            height: 1px;
            border-top: 2px dashed #e1e8ef;
            position: relative;
        }

        .plane-divider i {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--white);
            padding: 0 10px;
            color: var(--action-orange);
        }

        .time-box {
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #eee;
        }

        .price-tag {
            color: var(--primary-blue);
            font-size: 1.5rem;
            font-weight: 700;
        }

        .btn-book {
            background: var(--action-orange);
            color: var(--white);
            border: none;
            border-radius: 50px;
            font-weight: 600;
            padding: 10px 25px;
            transition: 0.3s;
        }

        .btn-book:hover {
            background: #e67e00;
            box-shadow: 0 5px 15px rgba(255,140,0,0.3);
            color: white;
            transform: scale(1.05);
        }

        .seats-left {
            font-size: 0.75rem;
            font-weight: 600;
            color: #d63031;
            background: #fff0f0;
            padding: 4px 12px;
            border-radius: 50px;
        }

        .btn-back {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue);
            font-weight: 600;
            border-radius: 50px;
        }

        .btn-back:hover {
            background: var(--primary-blue);
            color: white;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light mb-5 sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-paper-plane"></i> SKY<span>BOOK</span></a>
    </div>
</nav>

<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark m-0">Select Your Flight</h2>
            <p class="text-muted"><?= htmlspecialchars($source) ?> <i class="fas fa-arrow-right mx-2 small"></i> <?= htmlspecialchars($destination) ?></p>
        </div>
        <a href="index.php" class="btn btn-back px-4">
            <i class="fas fa-chevron-left me-2"></i> Back
        </a>
    </div>

    <div class="modify-box p-4 mb-5 shadow-sm">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">
            <input type="hidden" name="destination" value="<?= htmlspecialchars($destination) ?>">

            <div class="col-md-4">
                <label class="form-label">Update Travel Date</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent"><i class="far fa-calendar-alt text-primary"></i></span>
                    <input type="date" name="travel_date" class="form-control"
                           value="<?= $travel_date ? htmlspecialchars($travel_date) : '' ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100 py-2 fw-bold" style="border-radius: 10px; background: var(--primary-blue);">Refresh</button>
            </div>
        </form>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="text-center p-5 bg-white rounded-4 shadow-sm">
            <i class="fas fa-plane-slash fa-4x text-muted mb-3"></i>
            <h4 class="fw-bold">No Flights Available</h4>
            <p class="text-muted">We couldn't find any flights for the selected date. Please try another day.</p>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php while ($flight = $result->fetch_assoc()): ?>
            <div class="col-lg-4 col-md-6">
                <div class="flight-card p-4 shadow-sm h-100 d-flex flex-column">
                    
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div class="flight-name fs-5">
                            <i class="fas fa-plane-departure me-2"></i><?= htmlspecialchars($flight['flight_name']) ?>
                        </div>
                        <span class="seats-left"><?= (int)$flight['available_seats'] ?> Seats Available</span>
                    </div>

                    <div class="route-path mb-4">
                        <div class="text-center">
                            <span class="d-block fw-bold fs-5"><?= strtoupper(substr($flight['source'],0,3)) ?></span>
                            <small class="text-muted d-block"><?= htmlspecialchars($flight['source']) ?></small>
                        </div>
                        <div class="plane-divider">
                            <i class="fas fa-plane"></i>
                        </div>
                        <div class="text-center">
                            <span class="d-block fw-bold fs-5"><?= strtoupper(substr($flight['destination'],0,3)) ?></span>
                            <small class="text-muted d-block"><?= htmlspecialchars($flight['destination']) ?></small>
                        </div>
                    </div>

                    <div class="time-box p-3 mb-4 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Departure</small>
                            <span class="fw-bold"><?= date('H:i', strtotime($flight['departure_time'])) ?></span>
                        </div>
                        <div class="text-center border-start border-end px-3">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Travel Date</small>
                            <span class="fw-bold" style="font-size: 0.9rem;"><?= date('d M Y', strtotime($flight['departure_time'])) ?></span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Arrival</small>
                            <span class="fw-bold"><?= date('H:i', strtotime($flight['arrival_time'])) ?></span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <div class="price-tag">
                            <small class="fs-6 fw-normal text-muted">Rs.</small> <?= number_format($flight['price'], 2) ?>
                        </div>

                        <form method="POST" action="confirm.php">
                            <input type="hidden" name="flight_id" value="<?= (int)$flight['flight_id'] ?>">
                            <button class="btn btn-book">Book Now <i class="fas fa-chevron-right ms-2 small"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

</div>

<br><br>

</body>
</html>