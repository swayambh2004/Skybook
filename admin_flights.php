<?php
session_start();
include "db.php";

/* 🔐 1. SESSION SECURITY: Strict Admin Role Enforcement */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";

// --- BACKEND: ADD NEW FLIGHT (Prepared Statements) ---
if (isset($_POST['add_flight'])) {
    $name = $_POST['flight_name'];
    $src  = $_POST['source'];
    $dest = $_POST['destination'];
    $dep  = $_POST['departure_time'];
    $arr  = $_POST['arrival_time'];
    $prc  = (int)$_POST['price'];
    $seats = (int)$_POST['total_seats'];

    $stmt = $conn->prepare("INSERT INTO flights (flight_name, source, destination, departure_time, arrival_time, price, total_seats, available_seats) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiii", $name, $src, $dest, $dep, $arr, $prc, $seats, $seats);
    
    if ($stmt->execute()) { 
        $msg = "Flight added successfully!"; 
    }
    $stmt->close();
}

// --- BACKEND: UPDATE EXISTING FLIGHT (Prepared Statements) ---
if (isset($_POST['update_flight'])) {
    $id   = (int)$_POST['flight_id'];
    $name = $_POST['flight_name'];
    $prc  = (int)$_POST['price'];
    $dep  = $_POST['departure_time'];
    $arr  = $_POST['arrival_time'];

    $stmt = $conn->prepare("UPDATE flights SET flight_name=?, price=?, departure_time=?, arrival_time=? WHERE flight_id=?");
    $stmt->bind_param("sissi", $name, $prc, $dep, $arr, $id);
    
    if ($stmt->execute()) { 
        $msg = "Flight updated successfully!"; 
    }
    $stmt->close();
}

// --- BACKEND: DELETE FLIGHT (Prepared Statements) ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("SET FOREIGN_KEY_CHECKS = 0"); 
    
    $stmt = $conn->prepare("DELETE FROM flights WHERE flight_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    header("Location: admin_flights.php?msg=Deleted");
    exit();
}

$result = $conn->query("SELECT * FROM flights ORDER BY flight_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flight Inventory | Admin Dashboard</title>
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
            color: #1e293b;
        }

        /* --- SIDEBAR --- */
        .sidebar { 
            width: 280px; height: 100vh; position: fixed; 
            background: var(--sidebar-dark); color: white; padding: 30px 15px; z-index: 1000;
        }
        .sidebar-brand { font-size: 1.5rem; font-weight: 700; margin-bottom: 40px; text-align: center; }
        .sidebar-brand span { color: var(--action-orange); }
        .nav-link { color: rgba(255,255,255,0.7) !important; padding: 14px 20px; border-radius: 12px; transition: 0.3s; margin-bottom: 8px; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.1); color: white !important; }
        .nav-link.active { background: var(--action-orange); box-shadow: 0 4px 15px rgba(255,140,0,0.3); }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: 280px; padding: 50px; }
        
        /* --- INVENTORY CARD --- */
        .inventory-card { 
            background: var(--white); border-radius: 20px; 
            padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #edf2f7;
        }

        .btn-add { 
            background: var(--primary-blue); color: white; border: none; 
            border-radius: 12px; font-weight: 600; padding: 12px 25px; transition: 0.3s;
        }
        .btn-add:hover { background: #002a66; transform: translateY(-2px); color: white; }

        /* --- TABLE STYLING --- */
        .table thead th { 
            background: #f8fafc; color: #64748b; font-size: 0.75rem; 
            text-transform: uppercase; letter-spacing: 1px; padding: 15px; border: none;
        }
        .table tbody td { padding: 18px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .route-badge { 
            font-weight: 700; color: var(--primary-blue); background: #eef4ff; 
            padding: 4px 10px; border-radius: 6px; font-size: 0.85rem;
        }

        .status-pill {
            font-size: 0.7rem; font-weight: 700; padding: 4px 12px; border-radius: 50px;
            background: #f0fdf4; color: #16a34a;
        }

        .action-btn { 
            width: 35px; height: 35px; border-radius: 8px; border: none; 
            display: inline-flex; align-items: center; justify-content: center; transition: 0.2s;
            text-decoration: none;
        }
        .btn-manifest-ui { background: #e0f2fe; color: #0369a1; }
        .btn-edit-ui { background: #fff7ed; color: #ea580c; }
        .btn-delete-ui { background: #fef2f2; color: #dc2626; }
        
        .btn-manifest-ui:hover { background: #0369a1; color: white; }
        .btn-edit-ui:hover { background: #ea580c; color: white; }
        .btn-delete-ui:hover { background: #dc2626; color: white; }

        /* --- MODAL --- */
        .modal-content { border-radius: 24px; border: none; }
        .modal-header { border-bottom: 1px solid #f1f5f9; padding: 25px; }
        .form-control { border-radius: 10px; border: 2px solid #e2e8f0; padding: 12px; }
        .form-control:focus { border-color: var(--primary-blue); box-shadow: none; }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="sidebar-brand">
        <i class="fas fa-paper-plane me-2 text-white"></i>SKY<span>BOOK</span>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-chart-pie me-2"></i> Dashboard</a>
        <a class="nav-link active" href="admin_flights.php"><i class="fas fa-plane-departure me-2"></i> Manage Flights</a>
        <a class="nav-link text-danger mt-5" href="alogout.php"><i class="fas fa-power-off me-2"></i> Logout</a>
    </nav>
</div>

<div class="main-content">
    <?php if(isset($_GET['msg']) || $msg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-lg rounded-4 mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['msg'] ?? $msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold m-0">Flight Inventory</h2>
            <p class="text-muted">Operations and fleet management portal</p>
        </div>
        <button class="btn btn-add shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus-circle me-2"></i>Register New Flight
        </button>
    </div>

    <div class="inventory-card">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Flight Details</th>
                        <th>Route Path</th>
                        <th>Timing Schedule</th>
                        <th>Pricing</th>
                        <th>Capacity</th>
                        <th class="text-end">Operations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= $row['flight_name'] ?></div>
                            <small class="text-muted">ID: #FL-<?= $row['flight_id'] ?></small>
                        </td>
                        <td>
                            <span class="route-badge"><?= strtoupper(substr($row['source'], 0, 3)) ?></span>
                            <i class="fas fa-arrow-right mx-1 text-muted small"></i>
                            <span class="route-badge"><?= strtoupper(substr($row['destination'], 0, 3)) ?></span>
                        </td>
                        <td class="small text-muted">
                            <div class="mb-1"><i class="far fa-clock text-primary"></i> <?= date('d M, H:i', strtotime($row['departure_time'])) ?></div>
                            <div><i class="fas fa-plane-arrival"></i> <?= date('d M, H:i', strtotime($row['arrival_time'])) ?></div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">Rs. <?= number_format($row['price']) ?></div>
                            <span class="status-pill">Active</span>
                        </td>
                        <td>
                            <div class="fw-bold"><?= $row['available_seats'] ?> <small class="text-muted">/ <?= $row['total_seats'] ?></small></div>
                            <div class="progress mt-1" style="height: 4px; width: 60px;">
                                <div class="progress-bar bg-success" style="width: <?= ($row['available_seats']/$row['total_seats'])*100 ?>%"></div>
                            </div>
                        </td>
                        <td class="text-end">
                            <a href="admin_manifest.php?flight_id=<?= $row['flight_id'] ?>" 
                               class="action-btn btn-manifest-ui me-1" 
                               title="Passenger Manifest">
                               <i class="fas fa-users"></i>
                            </a>

                            <button class="action-btn btn-edit-ui edit-btn me-1" 
                                data-id="<?= $row['flight_id'] ?>"
                                data-name="<?= $row['flight_name'] ?>"
                                data-price="<?= $row['price'] ?>"
                                data-dep="<?= date('Y-m-d\TH:i', strtotime($row['departure_time'])) ?>"
                                data-arr="<?= date('Y-m-d\TH:i', strtotime($row['arrival_time'])) ?>"
                                data-bs-toggle="modal" data-bs-target="#editModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <a href="admin_flights.php?delete=<?= $row['flight_id'] ?>" 
                               class="action-btn btn-delete-ui" 
                               onclick="return confirm('CRITICAL: Delete this flight? This affects all linked tickets.')">
                               <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Deploy New Flight</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-12"><label class="form-label fw-bold">Flight Identity</label><input type="text" name="flight_name" class="form-control" placeholder="e.g. Skyline 747" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Origin</label><input type="text" name="source" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Destination</label><input type="text" name="destination" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">ETD (Departure)</label><input type="datetime-local" name="departure_time" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">ETA (Arrival)</label><input type="datetime-local" name="arrival_time" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Standard Fare (Rs.)</label><input type="number" name="price" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Payload (Seats)</label><input type="number" name="total_seats" class="form-control" required></div>
                    </div>
                </div>
                <div class="p-4 pt-0">
                    <button type="submit" name="add_flight" class="btn btn-add w-100 shadow-sm py-3">Publish to Global Inventory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Modify Flight Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="flight_id" id="edit_id">
                    <div class="mb-4"><label class="form-label fw-bold">Flight Name</label><input type="text" name="flight_name" id="edit_name" class="form-control" required></div>
                    <div class="mb-4"><label class="form-label fw-bold">Fare Adjust (Rs.)</label><input type="number" name="price" id="edit_price" class="form-control" required></div>
                    <div class="mb-4"><label class="form-label fw-bold">Updated ETD</label><input type="datetime-local" name="departure_time" id="edit_dep" class="form-control" required></div>
                    <div class="mb-4"><label class="form-label fw-bold">Updated ETA</label><input type="datetime-local" name="arrival_time" id="edit_arr" class="form-control" required></div>
                </div>
                <div class="p-4 pt-0">
                    <button type="submit" name="update_flight" class="btn btn-add w-100 py-3" style="background: var(--action-orange);">Push Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const editBtns = document.querySelectorAll('.edit-btn');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_price').value = this.dataset.price;
            document.getElementById('edit_dep').value = this.dataset.dep;
            document.getElementById('edit_arr').value = this.dataset.arr;
        });
    });
</script>
</body>
</html>