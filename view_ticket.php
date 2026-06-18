<?php
session_start();
include "db.php";

/* Check if user is logged in */
if (!isset($_SESSION['user_id'], $_GET['booking_id'])) {
    die("Invalid request. Please log in.");
}

$user_id = (int)$_SESSION['user_id'];
$booking_id = (int)$_GET['booking_id'];

// Fetch Flight and Booking Details
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_date, f.flight_name, f.source, f.destination,
           f.departure_time, f.arrival_time
    FROM bookings b
    JOIN flights f ON b.flight_id = f.flight_id
    WHERE b.booking_id=? AND b.user_id=?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$ticket_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket_info) die("Ticket not found.");

// Fetch individual passenger details including the new MEAL column
$stmt = $conn->prepare("SELECT seat, passenger_name, seat_class, meal_preference FROM booking_seats WHERE booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$res = $stmt->get_result();
$passengers = [];
while($r = $res->fetch_assoc()) { $passengers[] = $r; }
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Digital Boarding Pass | SkyBook</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/dist/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f0f2f5; margin: 0; padding: 40px 0; display: flex; flex-direction: column; align-items: center; gap: 30px; }
        .ticket-container { width: 780px; height: 260px; background: #fff; display: flex; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); position: relative; overflow: hidden; border: 1px solid #eee; }

        /* Color Coding Logic */
        .ticket-container.business { border-left: 12px solid #ff8c00; }
        .ticket-container.business .accent-text { color: #ff8c00; }
        .ticket-container.economy { border-left: 12px solid #0056b3; }
        .ticket-container.economy .accent-text { color: #0056b3; }

        .main-section { flex: 2; padding: 25px 35px; display: flex; flex-direction: column; justify-content: space-between; }
        .stub-section { flex: 0.8; background: #fafafa; border-left: 2px dashed #ddd; padding: 25px; position: relative; text-align: center; }
        
        /* Stub Notches */
        .stub-section::before, .stub-section::after { content: ""; position: absolute; width: 26px; height: 26px; background: #f0f2f5; border-radius: 50%; left: -14px; }
        .stub-section::before { top: -13px; } .stub-section::after { bottom: -13px; }

        .logo { font-weight: 800; font-size: 1.2rem; color: #333; margin: 0; letter-spacing: 1px; }
        .class-label { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; }

        .route { display: flex; align-items: center; gap: 15px; margin: 15px 0; }
        .city-code { font-size: 2.2rem; font-weight: 800; color: #222; margin: 0; }
        
        .label { font-size: 9px; color: #999; font-weight: 700; text-transform: uppercase; margin: 0; }
        .value { font-size: 13px; color: #333; font-weight: 700; margin: 0; }

        .barcode { font-family: 'Libre Barcode 128', cursive; font-size: 45px; margin: 10px 0 0; color: #222; }
        .btn-print { padding: 12px 35px; background: #333; color: white; border: none; border-radius: 50px; font-weight: 600; cursor: pointer; }

        @media print { .btn-print { display: none; } body { background: white; } .ticket-container { box-shadow: none; margin-bottom: 50px; page-break-inside: avoid; } }
    </style>
</head>
<body>

    <?php foreach($passengers as $p): 
        $themeClass = (strtolower($p['seat_class']) === 'business') ? 'business' : 'economy';
    ?>
    
    <div class="ticket-container <?= $themeClass ?>">
        
        <div class="main-section">
            <div class="d-flex justify-content-between align-items-center">
                <p class="logo">SKYBOOK</p>
                <p class="class-label accent-text"><?= htmlspecialchars(strtoupper($p['seat_class'])) ?> CLASS</p>
            </div>

            <div class="route">
                <div>
                    <p class="label">Origin</p>
                    <p class="city-code"><?= htmlspecialchars(strtoupper(substr($ticket_info['source'], 0, 3))) ?></p>
                </div>
                <div class="accent-text" style="font-size: 1.5rem;">✈</div>
                <div>
                    <p class="label">Destination</p>
                    <p class="city-code"><?= htmlspecialchars(strtoupper(substr($ticket_info['destination'], 0, 3))) ?></p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;">
                <div><p class="label">Passenger</p><p class="value"><?= htmlspecialchars($p['passenger_name']) ?></p></div>
                <div><p class="label">Flight</p><p class="value"><?= htmlspecialchars($ticket_info['flight_name']) ?></p></div>
                
                <div><p class="label">Date</p><p class="value"><?= date("d M Y", strtotime($ticket_info['departure_time'])) ?></p></div>

                <div>
                    <p class="label">In-flight Meal</p>
                    <p class="value accent-text"><i class="fas fa-utensils small"></i> <?= htmlspecialchars($p['meal_preference']) ?></p>
                </div>

                <div style="text-align: right;"><p class="label">Boarding</p><p class="value"><?= date("H:i", strtotime($ticket_info['departure_time'] . " -30 mins")) ?></p></div>
            </div>
        </div>

        <div class="stub-section">
            <p class="label">Seat Number</p>
            <p class="value accent-text" style="font-size: 2rem; line-height: 1.2;"><?= htmlspecialchars($p['seat']) ?></p>
            
            <div style="margin-top: 15px;">
                <p class="label">Booking Ref</p>
                <p class="value">#<?= (int)$ticket_info['booking_id'] ?></p>
            </div>

            <p class="barcode"><?= (int)$booking_id . htmlspecialchars($p['seat']) ?></p>
        </div>

    </div>
    <?php endforeach; ?>

    <button class="btn-print" onclick="window.print()">Print Boarding Passes</button>

</body>
</html>