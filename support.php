<?php include "db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Center | SkyBook</title>
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

        .navbar {
            background: var(--white) !important;
            padding: 15px 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            border-bottom: 3px solid var(--primary-blue);
        }
        .navbar-brand { font-weight: 700; color: var(--primary-blue) !important; }
        .navbar-brand i { color: var(--action-orange); }

        .support-hero {
            background: linear-gradient(135deg, rgba(0, 53, 128, 0.95), rgba(0, 86, 179, 0.8)), 
                        url('https://images.unsplash.com/photo-1436491865332-7a61a109c0f3?auto=format&fit=crop&w=1920&q=80') center/cover;
            color: white;
            padding: 120px 0;
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 85%);
        }

        .help-card {
            background: white;
            border: none;
            border-radius: 20px;
            padding: 40px 30px;
            transition: 0.3s;
            height: 100%;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 53, 128, 0.05);
        }
        .help-card:hover { transform: translateY(-10px); border-bottom: 4px solid var(--action-orange); }
        
        .icon-box {
            width: 70px; height: 70px;
            background: #f0f7ff;
            color: var(--primary-blue);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; margin: 0 auto 25px;
        }

        .advisory-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            border-left: 6px solid var(--action-orange);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 50px;
        }

        .btn-orange {
            background: var(--action-orange);
            color: white;
            border-radius: 50px;
            font-weight: 600;
            padding: 10px 25px;
            transition: 0.3s;
            border: none;
            text-decoration: none;
            display: inline-block;
        }
        .btn-orange:hover { background: #e67e00; color: white; }

        footer { background: var(--primary-blue); color: white; padding: 40px 0; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-paper-plane"></i> SKY<span>BOOK</span></a>
    </div>
</nav>

<section class="support-hero">
    <div class="container">
        <h1 class="display-5 fw-bold">Support Center</h1>
        <p class="lead opacity-75">Assisting your global travel needs.</p>
    </div>
</section>

<div class="container" style="margin-top: -50px;">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="help-card">
                <div class="icon-box"><i class="fas fa-tasks"></i></div>
                <h5 class="fw-bold">Manage Flight</h5>
                <p class="text-muted small">View details, update passenger info, or check your itinerary.</p>
                <a href="my_bookings.php" class="btn btn-orange btn-sm">My Bookings</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="help-card">
                <div class="icon-box"><i class="fas fa-times-circle"></i></div>
                <h5 class="fw-bold">How to Cancel Ticket</h5>
                <p class="text-muted small">Request immediate cancellation through our support channels.</p>
                <a href="#contact-info" class="btn btn-orange btn-sm">Cancel Now</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="help-card">
                <div class="icon-box"><i class="fas fa-info-circle"></i></div>
                <h5 class="fw-bold">Travel Advisory</h5>
                <p class="text-muted small">Important guidelines and baggage rules for your journey.</p>
                <a href="#travel-advisory" class="btn btn-orange btn-sm">View Guidelines</a>
            </div>
        </div>
    </div>
</div>

<div id="travel-advisory" class="container my-5 pt-5">
    <div class="advisory-box">
        <h4 class="fw-bold mb-4">Travel Advisory</h4>
        <ul class="list-unstyled">
            <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> <strong>Carrying Passport:</strong> Always carry your original passport and valid visa documents.</li>
            <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> <strong>Following Guidelines:</strong> Adhere to all airport security protocols and airline safety instructions.</li>
            <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> <strong>Baggage:</strong> Ensure your luggage meets the weight limits and does not contain prohibited items.</li>
        </ul>
    </div>
</div>

<div id="contact-info" class="py-5 bg-white border-top">
    <div class="container text-center">
        <h4 class="fw-bold mb-4">SkyBook Cancellation Desk</h4>
        <div class="row justify-content-center">
            <div class="col-md-4 mb-3">
                <p class="mb-1 fw-bold">Email Us</p>
                <a href="mailto:support@skybook.com" class="text-primary text-decoration-none">support@skybook.com</a>
            </div>
            <div class="col-md-4 mb-3">
                <p class="mb-1 fw-bold">Contact Number</p>
                <a href="tel:+18007592665" class="text-primary text-decoration-none">+1 (800) SKY-BOOK</a>
            </div>
        </div>
    </div>
</div>

<footer class="footer text-center">
    <div class="container">
        <p class="mb-0 small opacity-75">© 2026 SkyBook Global Support Infrastructure</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>