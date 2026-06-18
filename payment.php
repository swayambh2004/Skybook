<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}
$user_id = (int)$_SESSION['user_id'];

if (!isset($_POST['flight_id'], $_POST['passengers'], $_POST['travel_date'])) {
    die("Invalid request.");
}

$flight_id = (int)$_POST['flight_id'];
$passengers = $_POST['passengers']; 
$travel_date = $_POST['travel_date'];

$stmt = $conn->prepare("SELECT price, flight_name, source, destination, departure_time, arrival_time FROM flights WHERE flight_id = ?");
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$flight = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- UPDATED PRICING LOGIC (INCLUDING MEAL SURCHARGES) ---
$meal_costs = [
    'Standard (STML)' => 1000,
    'Vegetarian (VGML)' => 800,
    'Gluten-Free (GFML)' => 1500,
    'Diabetic (DBML)' => 850,
    'No Meal' => 0
];

$total_price = 0;
foreach($passengers as $p) {
    $multiplier = ($p['seat_class'] === 'business') ? 2 : 1;
    $base_fare = ($flight['price'] * $multiplier);
    
    // Add meal surcharge if it exists in the array
    $meal_surcharge = isset($meal_costs[$p['meal']]) ? $meal_costs[$p['meal']] : 0;
    
    $total_price += ($base_fare + $meal_surcharge);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyBound | Secure Payment</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/dist/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html { height: 100%; font-family: 'Poppins', sans-serif; background: #0b1d2a; color: white; }
        .bg-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; background: linear-gradient(135deg, #0b1d2a 0%, #1c3d5a 100%); }
        .hero { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .glass-card { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 30px; padding: 30px; max-width: 550px; width: 100%; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5); }
        .passenger-summary { background: rgba(255, 255, 255, 0.05); border-left: 4px solid #00d4ff; margin-bottom: 10px; padding: 12px; border-radius: 10px; }
        .business-border { border-left: 4px solid #ff8c00 !important; }
        input.form-control { background: rgba(255, 255, 255, 0.9); border: 1px solid #fff; color: #000 !important; border-radius: 10px; font-weight: 500; }
        label { color: #00d4ff; font-weight: 600; font-size: 0.8rem; }
        .btn-pay { background: #00d4ff; color: #000; padding: 15px; border-radius: 50px; border: none; font-weight: bold; width: 100%; margin-top: 20px; transition: 0.3s; }
        .btn-pay:hover { background: #00b4d8; transform: translateY(-2px); }
        #loader-container { display: none; text-align: center; }
        .loader { color: white; font-family: "Poppins", sans-serif; font-weight: 500; font-size: 25px; display: flex; justify-content: center; }
        .words { overflow: hidden; height: 40px; }
        .word { display: block; height: 100%; padding-left: 6px; color: #00d4ff; animation: spin_499 4s infinite; }
        @keyframes spin_499 { 10% { transform: translateY(-102%); } 25% { transform: translateY(-100%); } 35% { transform: translateY(-202%); } 50% { transform: translateY(-200%); } 65% { transform: translateY(-302%); } 80% { transform: translateY(-300%); } 95% { transform: translateY(-402%); } 100% { transform: translateY(-400%); } }
    </style>
</head>
<body>

    <div class="bg-container"></div>

    <section class="hero">
        <div class="glass-card" id="main-card">
            <h2 class="text-center mb-1">Confirm & Pay</h2>
            <p class="text-center text-info mb-4 small"><?= htmlspecialchars($flight['flight_name']) ?> | Total: Rs. <?= number_format($total_price, 2) ?></p>

            <div class="mb-4" style="max-height: 250px; overflow-y: auto; padding-right: 5px;">
                <?php foreach($passengers as $i => $p): 
                    $isBusiness = ($p['seat_class'] === 'business');
                ?>
                <div class="passenger-summary <?= $isBusiness ? 'business-border' : '' ?>">
                    <div class="row small">
                        <div class="col-8">
                            <strong>Seat <?= htmlspecialchars($p['seat']) ?></strong> 
                            <span class="badge <?= $isBusiness ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= htmlspecialchars($p['seat_class']) ?></span>
                        </div>
                        <div class="col-4 text-end text-info"><?= htmlspecialchars($p['meal']) ?></div>
                        <div class="col-12 mt-1 fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <form id="paymentForm" method="POST" action="process_payment.php">
                <input type="hidden" name="flight_id" value="<?= (int)$flight_id ?>">
                <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
                <input type="hidden" name="total_price" value="<?= (float)$total_price ?>">
                <input type="hidden" name="travel_date" value="<?= htmlspecialchars($travel_date) ?>">

                <?php foreach($passengers as $i => $p): ?>
                    <input type="hidden" name="passengers[<?= $i ?>][seat]" value="<?= htmlspecialchars($p['seat']) ?>">
                    <input type="hidden" name="passengers[<?= $i ?>][seat_class]" value="<?= htmlspecialchars($p['seat_class']) ?>">
                    <input type="hidden" name="passengers[<?= $i ?>][name]" value="<?= htmlspecialchars($p['name']) ?>">
                    <input type="hidden" name="passengers[<?= $i ?>][age]" value="<?= (int)$p['age'] ?>">
                    <input type="hidden" name="passengers[<?= $i ?>][contact]" value="<?= htmlspecialchars($p['contact']) ?>">
                    <input type="hidden" name="passengers[<?= $i ?>][meal]" value="<?= htmlspecialchars($p['meal']) ?>">
                <?php endforeach; ?>

                <div class="mb-3">
                    <label>NAME ON CARD</label>
                    <input type="text" class="form-control" name="card_name" required placeholder="Full Name">
                </div>
                <div class="mb-3">
                    <label>CARD NUMBER</label>
                    <input type="text" class="form-control" name="card_number" pattern="\d{16}" maxlength="16" required placeholder="16 Digit Card Number">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label>EXPIRY</label>
                        <input type="text" class="form-control" name="expiry" placeholder="MM/YY" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label>CVV</label>
                        <input type="password" class="form-control" name="cvv" maxlength="3" required placeholder="***">
                    </div>
                </div>

                <button type="button" class="btn-pay" onclick="startLoading()">
                    PAY Rs. <?= number_format($total_price, 2) ?> <i class="fas fa-lock ms-2"></i>
                </button>
            </form>
        </div>

        <div id="loader-container">
            <div class="loader">
                <p>Verifying</p>
                <div class="words">
                    <span class="word">Identity</span>
                    <span class="word">Funds</span>
                    <span class="word">Meals</span>
                    <span class="word">Tickets</span>
                    <span class="word">Identity</span>
                </div>
            </div>
        </div>
    </section>

    <script>
        function startLoading() {
            const form = document.getElementById('paymentForm');
            if(!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            document.getElementById('main-card').style.display = 'none';
            document.getElementById('loader-container').style.display = 'block';
            setTimeout(() => { form.submit(); }, 4000);
        }
    </script>
</body>
</html>