<?php
session_start();
require_once "db.php"; // Strict require inclusion

/* 🔐 Ensure user is logged in */
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}
$user_id = (int)$_SESSION['user_id'];

/* ✅ Validate required POST data */
if (!isset($_POST['flight_id'], $_POST['travel_date'])) {
    die("Invalid request.");
}
$flight_id = (int)$_POST['flight_id'];
$travel_date = $_POST['travel_date'];

/* 🔹 Fetch flight details (Updated to ensure we have departure_time for the date) */
$stmt = $conn->prepare("
    SELECT flight_name, source, destination, departure_time, arrival_time, price 
    FROM flights WHERE flight_id=?
");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$flight) {
    die("Flight record not found.");
}

/* 🔹 Extract Travel Date directly from Database timestamp */
$db_travel_date = date('Y-m-d', strtotime($flight['departure_time']));
/* Use the database date if it differs from the POST date */
$display_date = $db_travel_date;

/* 🔹 Seat map dimensions */
$rows = 6;
$cols = 6;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seat Selection | SkyBook</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    body { background:#e9ecef; font-family:'Poppins', sans-serif; }
    .container { max-width:850px; margin-top:30px; padding-bottom: 50px; }
    
    /* --- AIRPLANE FUSELAGE DESIGN --- */
    .plane-body {
        background: #ffffff;
        border: 4px solid #adb5bd;
        border-radius: 150px 150px 30px 30px; /* Nose to Tail shape */
        padding: 100px 40px 60px;
        position: relative;
        box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        margin: 0 auto;
        max-width: 500px;
    }

    /* Windows on the side */
    .plane-body::before, .plane-body::after {
        content: "";
        position: absolute;
        top: 150px;
        bottom: 50px;
        width: 10px;
        background-image: radial-gradient(circle, #87ceeb 60%, transparent 70%);
        background-size: 10px 40px;
    }
    .plane-body::before { left: 10px; }
    .plane-body::after { right: 10px; }

    /* Cockpit Window */
    .cockpit {
        position: absolute;
        top: 30px;
        left: 50%;
        transform: translateX(-50%);
        width: 180px;
        height: 40px;
        background: #343a40;
        border-radius: 40px 40px 10px 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 0.7rem;
        font-weight: bold;
        letter-spacing: 2px;
    }

    .seat-map { 
        display:grid; 
        grid-template-columns: 35px repeat(3, 50px) 20px repeat(3, 50px); 
        gap: 12px; 
        justify-content: center; 
        position: relative;
        z-index: 2;
    }

    /* --- SEAT COLORS --- */
    .seat { 
        width:50px; height:50px; border-radius:8px; text-align:center; 
        line-height:50px; cursor:pointer; transition:.2s; font-size: 0.8rem; 
        border: 1px solid #ccc; font-weight: 600;
    }
    
    .seat.business { background: #ff8c00; color: white; border-color: #e67e00; } 
    .seat.economy { background: #a59e9e; color: white; border-color: #8e8787; }
    .seat.selected { background:#28a745 !important; color:#fff !important; border-color: #1e7e34 !important; transform: scale(1.1); }
    .seat.booked { background:#dc3545 !important; color:#fff !important; cursor:not-allowed !important; opacity: 0.6; }

    .seat:hover:not(.booked):not(.selected) { filter: brightness(1.2); }

    .row-label { text-align:center; font-weight:bold; line-height:50px; color: #6c757d; }
    
    .legend { 
        margin-top: 40px; display:flex; justify-content:center; gap: 20px; 
        background: white; padding: 15px; border-radius: 15px; border: 1px solid #ddd;
    }
    .legend div { display:flex; align-items:center; gap:8px; font-size: 0.9rem; }
    .legend .box { width:18px; height:18px; border-radius:4px; }
    .box.business { background: #ff8c00; }
    .box.economy { background: #a59e9e; }
    .box.selected { background: #28a745; }
    .box.booked { background: #dc3545; }

    .passenger-form { 
        background:#fff; padding:25px; border-radius:20px; margin-top:20px; 
        border: 1px solid #dee2e6; box-shadow: 0 10px 20px rgba(0,0,0,0.05); 
    }
    .badge-class { font-size: 0.7rem; padding: 4px 10px; border-radius: 20px; }
</style>
</head>

<body>
<div class="container">

    <div class="card p-4 mb-4 shadow-sm border-0" style="border-radius: 20px;">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3 class="fw-bold mb-1">Flight: <?= htmlspecialchars($flight['flight_name']) ?></h3>
                <p class="text-muted mb-2"><?= htmlspecialchars($flight['source']) ?> ✈ <?= htmlspecialchars($flight['destination']) ?></p>
                <div class="badge bg-primary px-3 py-2" style="border-radius: 8px;">
                    <i class="far fa-calendar-alt me-2"></i> Travel Date: <?= date('d M Y', strtotime($display_date)) ?>
                </div>
            </div>
            <div class="text-end">
                <div class="mb-1" style="color: #ff8c00;"><strong>Business:</strong> Rs. <?= number_format($flight['price'] * 2, 2) ?></div>
                <div style="color: #6c757d;"><strong>Economy:</strong> Rs. <?= number_format($flight['price'], 2) ?></div>
            </div>
        </div>
    </div>

    <form method="POST" action="payment.php" onsubmit="return validateSeats();">
        <input type="hidden" name="flight_id" value="<?= (int)$flight_id ?>">
        <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
        <input type="hidden" name="travel_date" value="<?= htmlspecialchars($display_date) ?>">
        <input type="hidden" name="selected_seats" id="selectedSeats">

        <div class="plane-body">
            <div class="cockpit">FLIGHT CREW ONLY</div>
            
            <div class="seat-map" id="seatMap">
                <?php
                for ($r = 1; $r <= $rows; $r++) {
                    $rowLetter = chr(64 + $r);
                    $seatClass = ($r <= 2) ? 'business' : 'economy';
                    
                    echo "<div class='row-label'>$rowLetter</div>";
                    for ($c = 1; $c <= 3; $c++) {
                        $seat = $rowLetter . $c;
                        echo "<div class='seat $seatClass' data-seat='$seat' data-class='$seatClass'>$seat</div>";
                    }
                    echo "<div></div>"; // Aisle
                    for ($c = 4; $c <= 6; $c++) {
                        $seat = $rowLetter . $c;
                        echo "<div class='seat $seatClass' data-seat='$seat' data-class='$seatClass'>$seat</div>";
                    }
                }
                ?>
            </div>
        </div>

        <div class="legend shadow-sm">
            <div><div class="box business"></div> Business</div>
            <div><div class="box economy"></div> Economy</div>
            <div><div class="box selected"></div> Selected</div>
            <div><div class="box booked"></div> Booked</div>
        </div>

        <div id="passengerDetailsContainer"></div>

        <div class="mt-5 text-center">
            <button type="submit" class="btn btn-primary btn-lg px-5 py-3 shadow" style="border-radius: 50px; font-weight: 600;">
                PROCEED TO BOOK
            </button>
        </div>
    </form>
</div>

<script>
let selectedSeats = [];
const selectedSeatsInput = document.getElementById('selectedSeats');
const passengerDetailsContainer = document.getElementById('passengerDetailsContainer');

function seatClickHandler() {
    const seatId = this.dataset.seat;
    if (selectedSeats.includes(seatId)) {
        selectedSeats = selectedSeats.filter(s => s !== seatId);
        this.classList.remove('selected');
    } else {
        selectedSeats.push(seatId);
        this.classList.add('selected');
    }
    selectedSeatsInput.value = selectedSeats.join(',');
    renderPassengerForms();
}

function renderPassengerForms() {
    passengerDetailsContainer.innerHTML = '';
    selectedSeats.forEach((seat, index) => {
        const seatElem = document.querySelector(`[data-seat="${seat}"]`);
        const seatClass = seatElem.dataset.class;
        const badgeColor = (seatClass === 'business') ? 'bg-warning text-dark' : 'bg-secondary text-white';

        const form = document.createElement('div');
        form.className = 'passenger-form animate__animated animate__fadeInUp';
        form.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-0 fw-bold">Passenger ${index+1} <small class="text-muted">(Seat ${seat})</small></h5>
                <span class="badge ${badgeColor} badge-class">${seatClass}</span>
            </div>
            
            <input type="hidden" name="passengers[${index}][seat]" value="${seat}">
            <input type="hidden" name="passengers[${index}][seat_class]" value="${seatClass}">
            
            <div class="mb-3">
                <label class="form-label small fw-bold">FULL NAME</label>
                <input type="text" class="form-control" name="passengers[${index}][name]" placeholder="As per Passport/ID" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">MEAL PREFERENCE</label>
                <select class="form-select" name="passengers[${index}][meal]" required>
                    <option value="Standard (STML)">Standard (Chicken or Fish) [+ Rs. 1000]</option>
                    <option value="Vegetarian (VGML)">Vegetarian [+ Rs. 800]</option>
                    <option value="Gluten-Free (GFML)">Gluten-Free [+ Rs. 1500]</option>
                    <option value="Diabetic (DBML)">Low Sugar (Diabetic Friendly) [+ Rs. 850]</option>
                    <option value="No Meal">No Meal Required [Rs. 0]</option>
                </select>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label small fw-bold">AGE</label>
                    <input type="number" class="form-control" name="passengers[${index}][age]" min="1" max="120" required>
                </div>
                <div class="col-md-8 mb-3">
                    <label class="form-label small fw-bold">CONTACT NUMBER</label>
                    <input type="text" class="form-control" name="passengers[${index}][contact]" placeholder="Mobile Number" required>
                </div>
            </div>
        `;
        passengerDetailsContainer.appendChild(form);
    });
}

function validateSeats() {
    if (selectedSeats.length === 0) {
        alert("Please select your seat(s) before proceeding.");
        return false;
    }
    return true;
}

function bindSeatClicks() {
    document.querySelectorAll('.seat').forEach(seat => {
        if (!seat.classList.contains('booked')) {
            seat.onclick = seatClickHandler;
        } else {
            seat.onclick = null;
        }
    });
}

function loadBookedSeats() {
    fetch(`get_booked_seats.php?flight_id=<?= (int)$flight_id ?>&date=<?= $display_date ?>`)
        .then(res => res.json())
        .then(bookedSeats => {
            document.querySelectorAll('.seat').forEach(seat => {
                const seatId = seat.dataset.seat;
                if (bookedSeats.includes(seatId)) {
                    seat.classList.add('booked');
                    seat.classList.remove('selected');
                    if(selectedSeats.includes(seatId)){
                        selectedSeats = selectedSeats.filter(s => s !== seatId);
                        renderPassengerForms();
                    }
                } else {
                    seat.classList.remove('booked');
                }
            });
            bindSeatClicks();
            selectedSeatsInput.value = selectedSeats.join(',');
        });
}

loadBookedSeats();
setInterval(loadBookedSeats, 5000); 
</script>
</body>
</html>