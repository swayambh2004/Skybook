<?php
session_start();
include "db.php";

/* 🔐 Security Check */
if (!isset($_SESSION['user_id'])) die("You must be logged in.");
$user_id = (int)$_SESSION['user_id'];

/* ✅ Validate POST Data */
if (!isset($_POST['flight_id'], $_POST['passengers'], $_POST['total_price'])) {
    die("Missing data. Connection lost.");
}

$flight_id = (int)$_POST['flight_id'];
$passengers = $_POST['passengers']; 
$total_amount = (double)$_POST['total_price'];
$travel_date = $_POST['travel_date'];

$selected_seats = array_column($passengers, 'seat');

$conn->begin_transaction();

try {
    /* 1. Conflict check */
    $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
    $stmt = $conn->prepare("SELECT seat FROM booking_seats WHERE flight_id=? AND seat IN ($placeholders)");
    $stmt->bind_param("i" . str_repeat("s", count($selected_seats)), $flight_id, ...$selected_seats);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) throw new Exception("One or more seats were just taken.");
    $stmt->close();

    /* 2. Insert main booking record */
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, flight_id, total_amount, booking_date) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iid", $user_id, $flight_id, $total_amount);
    $stmt->execute();
    $booking_id = $stmt->insert_id;
    $stmt->close();

    /* 3. Insert seats WITH passenger details, class, AND MEAL */
    $stmt = $conn->prepare("
        INSERT INTO booking_seats (
            booking_id, 
            flight_id, 
            seat, 
            user_id, 
            seat_class, 
            passenger_name, 
            passenger_age, 
            passenger_contact, 
            meal_preference
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($passengers as $p) {
        $seat = $p['seat'];
        $p_class = $p['seat_class'];
        $p_name = $p['name'];
        $p_age = (int)$p['age'];
        $p_contact = $p['contact'];
        $p_meal = $p['meal']; 

        /* i = int, s = string*/
        $stmt->bind_param(
            "iisississ", 
            $booking_id, 
            $flight_id, 
            $seat, 
            $user_id, 
            $p_class, 
            $p_name, 
            $p_age, 
            $p_contact, 
            $p_meal
        );
        $stmt->execute();
    }
    $stmt->close();

    /* 4. 🛡️ SECURITY FIX: Parameterize the remaining available seats count query */
    $seat_count = count($selected_seats);
    $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats - ? WHERE flight_id = ?");
    $stmt->bind_param("ii", $seat_count, $flight_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    die("<div style='padding:20px; font-family:sans-serif;'>Error: " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirmed | SkyBound</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            color: #333;
        }

        .success-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            max-width: 480px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .check-icon {
            color: #27ae60;
            font-size: 48px;
            margin-bottom: 15px;
        }

        <h2> { font-weight: 600; font-size: 1.5rem; margin-bottom: 5px; color: #1a1a1a; }
        .booking-id { font-size: 0.9rem; color: #888; margin-bottom: 30px; }

        .manifest {
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            padding: 20px 0;
            margin-bottom: 30px;
            text-align: left;
        }

        .p-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .p-row:last-child { margin-bottom: 0; }

        .p-name { color: #444; }
        .p-seat { font-weight: 600; color: #1a1a1a; }

        .total-info { margin-bottom: 35px; }
        .total-info small { color: #999; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px; }
        .total-amount { display: block; font-size: 1.25rem; font-weight: 600; color: #333; }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #888;
            text-decoration: none;
        }

        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="success-card">
    <div class="check-icon">✓</div>
    <h2>Booking Confirmed</h2>
    <div class="booking-id">Order ID: #<?= $booking_id ?></div>

    <div class="manifest">
        <?php foreach($passengers as $p): ?>
            <div class="p-row">
                <span class="p-name"><?= htmlspecialchars($p['name']) ?></span>
                <span class="p-seat"><?= htmlspecialchars($p['seat']) ?> (<?= ucfirst($p['seat_class']) ?>)</span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="total-info">
        <small>Total Amount Paid</small>
        <span class="total-amount">Rs. <?= number_format($total_amount, 2) ?></span>
    </div>

    <a href="index.php" class="back-link">Return to Home</a>
</div>

</body>
</html>