<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'], $_POST['booking_id'])) {
    die("Invalid request");
}

$user_id = (int)$_SESSION['user_id'];
$booking_id = (int)$_POST['booking_id'];

$conn->begin_transaction();

try {
    /* Verify ownership and fetch flight identity details */
    $stmt = $conn->prepare(
        "SELECT booking_id, flight_id FROM bookings WHERE booking_id=? AND user_id=?"
    );
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking_result = $stmt->get_result();
    
    if ($booking_result->num_rows === 0) {
        throw new Exception("Unauthorized");
    }
    
    $booking_data = $booking_result->fetch_assoc();
    $flight_id = (int)$booking_data['flight_id'];
    $stmt->close();

    /* Count seats held by this booking to restore to fleet payload inventory */
    $stmt = $conn->prepare("SELECT COUNT(*) as seat_count FROM booking_seats WHERE booking_id=?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $seat_count = $stmt->get_result()->fetch_assoc()['seat_count'] ?? 0;
    $stmt->close();

    if ($seat_count > 0) {
        /* 🛡️ INVENTORY RESTORATION FIX: Add the seats back to available capacity */
        $stmt = $conn->prepare("UPDATE flights SET available_seats = available_seats + ? WHERE flight_id = ?");
        $stmt->bind_param("ii", $seat_count, $flight_id);
        $stmt->execute();
        $stmt->close();
    }

    /* Delete seats */
    $stmt = $conn->prepare("DELETE FROM booking_seats WHERE booking_id=?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->close();

    /* Delete booking */
    $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_id=?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    header("Location: my_bookings.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Error cancelling ticket");
}