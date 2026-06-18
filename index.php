<?php 
session_start();
include "db.php"; 

/* 🔐 Ensure user is logged in before accessing search utility */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SkyBook | Global Flight Systems</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-blue: #003580;    /* Deep Professional Blue */
            --action-orange: #ff8c00;   /* High-Visibility Orange */
            --bg-light: #f0f4f8;        /* Soft Aviation Gray */
            --white: #ffffff;
            --text-dark: #002244;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            margin: 0;
            overflow-x: hidden;
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
            font-size: 1.6rem;
            color: var(--primary-blue) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-brand i { color: var(--action-orange); }
        
        .nav-link {
            color: var(--primary-blue) !important;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 12px;
            transition: 0.3s;
        }
        .nav-link:hover { color: var(--action-orange) !important; }
        
        .btn-logout {
            border: 2px solid var(--primary-blue);
            color: var(--primary-blue) !important;
            border-radius: 50px !important;
            padding: 6px 20px !important;
        }
        .btn-logout:hover {
            background: var(--primary-blue);
            color: var(--white) !important;
        }

        /* --- HERO SECTION --- */
        .hero {
            background: linear-gradient(135deg, rgba(0, 53, 128, 0.85), rgba(0, 53, 128, 0.6)),
                        url('https://images.unsplash.com/photo-1436491865332-7a61a109c0f3?auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-align: center;
            clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%);
        }
        .hero h1 { font-size: 3.5rem; font-weight: 700; margin-bottom: 10px; }
        .hero p { font-size: 1.2rem; font-weight: 300; opacity: 0.9; }

        /* --- SEARCH BOX WIDGET --- */
        .search-box {
            background: var(--white);
            border-radius: 20px;
            padding: 40px;
            margin-top: -120px;
            box-shadow: 0 15px 40px rgba(0, 53, 128, 0.15);
            position: relative;
            z-index: 10;
        }
        .search-box::after {
            content: "";
            position: absolute;
            top: 0; left: 50%;
            transform: translateX(-50%);
            width: 100px; height: 5px;
            background: var(--action-orange);
            border-radius: 0 0 10px 10px;
        }
        
        .form-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--primary-blue);
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .form-control {
            border: 2px solid #e1e8ef;
            border-radius: 12px;
            padding: 14px;
            font-weight: 500;
            transition: 0.3s;
        }
        .form-control:focus {
            border-color: var(--action-orange);
            box-shadow: none;
            outline: none;
        }

        .btn-search {
            background: var(--action-orange);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
            height: 58px;
        }
        .btn-search:hover {
            background: #e67e00;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 140, 0, 0.3);
            color: white;
        }

        /* --- EARTH LOADER OVERLAY (UIVERSE.IO) --- */
        #loader-overlay {
            position: fixed;
            inset: 0;
            background: var(--primary-blue); 
            display: none; 
            z-index: 10000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .earth-loader {
            --watercolor: #3344c1;
            --landcolor: #7cc133;
            width: 7.5em;
            height: 7.5em;
            background-color: var(--watercolor);
            position: relative;
            overflow: hidden;
            border-radius: 50%;
            box-shadow: inset 0em 0.5em rgb(255, 255, 255, 0.25), inset 0em -0.5em rgb(0, 0, 0, 0.25);
            border: solid 0.15em white;
            animation: startround 1s;
            animation-iteration-count: 1;
        }

        .earth p {
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            padding-top: 0.25em;
            font-size: 1.25em;
            font-family: "Poppins", sans-serif;
        }

        .earth-loader svg {
            position: absolute;
            width: 7em;
            height: auto;
        }

        .earth-loader svg:nth-child(1) { bottom: -2em; animation: round1 5s infinite linear 0.75s; }
        .earth-loader svg:nth-child(2) { top: -3em; animation: round1 5s infinite linear; }
        .earth-loader svg:nth-child(3) { top: -2.5em; animation: round2 5s infinite linear; }
        .earth-loader svg:nth-child(4) { bottom: -2.2em; animation: round2 5s infinite linear 0.75s; }

        @keyframes startround {
            0% { filter: brightness(500%); box-shadow: none; }
            75% { filter: brightness(500%); box-shadow: none; }
            100% { filter: brightness(100%); box-shadow: inset 0em 0.5em rgb(255, 255, 255, 0.25), inset 0em -0.5em rgb(0, 0, 0, 0.25); }
        }

        @keyframes round1 {
            0% { left: -2em; opacity: 100%; transform: skewX(0deg) rotate(0deg); }
            30% { left: -6em; opacity: 100%; transform: skewX(-25deg) rotate(25deg); }
            31% { left: -6em; opacity: 0%; transform: skewX(-25deg) rotate(25deg); }
            35% { left: 7em; opacity: 0%; transform: skewX(25deg) rotate(-25deg); }
            45% { left: 7em; opacity: 100%; transform: skewX(25deg) rotate(-25deg); }
            100% { left: -2em; opacity: 100%; transform: skewX(0deg) rotate(0deg); }
        }

        @keyframes round2 {
            0% { left: 5em; opacity: 100%; transform: skewX(0deg) rotate(0deg); }
            75% { left: -7em; opacity: 100%; transform: skewX(-25deg) rotate(25deg); }
            76% { left: -7em; opacity: 0%; transform: skewX(-25deg) rotate(25deg); }
            77% { left: 8em; opacity: 0%; transform: skewX(25deg) rotate(-25deg); }
            80% { left: 8em; opacity: 100%; transform: skewX(25deg) rotate(-25deg); }
            100% { left: 5em; opacity: 100%; transform: skewX(0deg) rotate(0deg); }
        }

        footer {
            background: var(--primary-blue);
            color: var(--white);
            padding: 50px 0;
            margin-top: 100px;
        }
        .footer-logo { font-size: 1.5rem; font-weight: 700; margin-bottom: 10px; }
        .footer-logo span { color: var(--action-orange); }
    </style>
</head>
<body>

<div id="loader-overlay">
    <div class="earth">
        <div class="earth-loader">
            <svg viewBox="0 0 200 200"><path transform="translate(100 100)" d="M29.4,-17.4C33.1,1.8,27.6,16.1,11.5,31.6C-4.7,47,-31.5,63.6,-43,56C-54.5,48.4,-50.7,16.6,-41,-10.9C-31.3,-38.4,-15.6,-61.5,-1.4,-61C12.8,-60.5,25.7,-36.5,29.4,-17.4Z" fill="#7CC133"></path></svg>
            <svg viewBox="0 0 200 200"><path transform="translate(100 100)" d="M31.7,-55.8C40.3,-50,45.9,-39.9,49.7,-29.8C53.5,-19.8,55.5,-9.9,53.1,-1.4C50.6,7.1,43.6,14.1,41.8,27.6C40.1,41.1,43.4,61.1,37.3,67C31.2,72.9,15.6,64.8,1.5,62.2C-12.5,59.5,-25,62.3,-31.8,56.7C-38.5,51.1,-39.4,37.2,-49.3,26.3C-59.1,15.5,-78,7.7,-77.6,0.2C-77.2,-7.2,-57.4,-14.5,-49.3,-28.4C-41.2,-42.4,-44.7,-63,-38.5,-70.1C-32.2,-77.2,-16.1,-70.8,-2.3,-66.9C11.6,-63,23.1,-61.5,31.7,-55.8Z" fill="#7CC133"></path></svg>
            <svg viewBox="0 0 200 200"><path transform="translate(100 100)" d="M30.6,-49.2C42.5,-46.1,57.1,-43.7,67.6,-35.7C78.1,-27.6,84.6,-13.8,80.3,-2.4C76.1,8.9,61.2,17.8,52.5,29.1C43.8,40.3,41.4,53.9,33.7,64C26,74.1,13,80.6,2.2,76.9C-8.6,73.1,-17.3,59,-30.6,52.1C-43.9,45.3,-61.9,45.7,-74.1,38.2C-86.4,30.7,-92.9,15.4,-88.6,2.5C-84.4,-10.5,-69.4,-20.9,-60.7,-34.6C-52.1,-48.3,-49.8,-65.3,-40.7,-70C-31.6,-74.8,-15.8,-67.4,-3.2,-61.8C9.3,-56.1,18.6,-52.3,30.6,-49.2Z" fill="#7CC133"></path></svg>
            <svg viewBox="0 0 200 200"><path transform="translate(100 100)" d="M39.4,-66C48.6,-62.9,51.9,-47.4,52.9,-34.3C53.8,-21.3,52.4,-10.6,54.4,1.1C56.3,12.9,61.7,25.8,57.5,33.2C53.2,40.5,39.3,42.3,28.2,46C17,49.6,8.5,55.1,1.3,52.8C-5.9,50.5,-11.7,40.5,-23.6,37.2C-35.4,34,-53.3,37.5,-62,32.4C-70.7,27.4,-70.4,13.7,-72.4,-1.1C-74.3,-15.9,-78.6,-31.9,-73.3,-43C-68.1,-54.2,-53.3,-60.5,-39.5,-60.9C-25.7,-61.4,-12.9,-56,1.1,-58C15.1,-59.9,30.2,-69.2,39.4,-66Z" fill="#7CC133"></path></svg>
        </div>
        <p>Connecting...</p>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-paper-plane"></i> SKY<span>BOOK</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="support.php">Support</a></li>
                <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                <li class="nav-item ms-lg-3">
                    <a class="nav-link btn-logout" href="logout.php">Sign Out</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <h1>Navigate Your World</h1>
        <p>Premium flight systems for the modern explorer.</p>
    </div>
</section>

<div class="container">
    <div class="search-box">
        <form id="searchForm" method="GET" action="booking.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Leaving From</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-map-marker-alt text-muted"></i></span>
                    <input type="text" name="source" class="form-control border-start-0" placeholder="Origin City" required>
                </div>
            </div>

            <div class="col-md-5">
                <label class="form-label">Going To</label>
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-plane-arrival text-muted"></i></span>
                    <input type="text" name="destination" class="form-control border-start-0" placeholder="Destination City" required>
                </div>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-search w-100">
                    Explore <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<footer class="footer">
    <div class="container text-center">
        <div class="footer-logo"><i class="fas fa-paper-plane"></i> SKY<span>BOOK</span></div>
        <p class="small opacity-75">Global Flight Reservation & Tracking Infrastructure</p>
        <hr class="my-4 opacity-25">
        <p class="small mb-0">© 2026 SkyBook Airlines. All Rights Reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const loader = document.getElementById('loader-overlay');
        loader.style.display = 'flex';
        
        setTimeout(() => {
            this.submit();
        }, 2200); 
    });
</script>

</body>
</html>