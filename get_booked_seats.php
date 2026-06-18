<?php
include "db.php";
if (!isset($_GET['flight_id'])) die(json_encode([]));
$flight_id = (int)$_GET['flight_id'];

$stmt = $conn->prepare("SELECT seat FROM booking_seats WHERE flight_id=?");
$stmt->bind_param("i",$flight_id);
$stmt->execute();
$result = $stmt->get_result();

$booked_seats = [];
while($row=$result->fetch_assoc()){
    $booked_seats[] = $row['seat'];
}

echo json_encode($booked_seats);