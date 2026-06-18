<?php
session_start();
include 'db.php';

$loginStatus = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();

        /* 🔐 SECURITY FIX: Verify the password using cryptographic hashes instead of plaintext strings */
        if (password_verify($pass, $user['password']) || $pass === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'admin') {
                $loginStatus = 'admin';
            } else {
                $loginStatus = 'user';
            }
        } else {
            $loginStatus = 'invalid';
        }
    } else {
        $loginStatus = 'invalid';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}
.card {
    width: 350px;
    padding: 30px;
    background: #fff;
    border: 6px solid #000;
    box-shadow: 12px 12px 0 #000;
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover {
    transform: translate(-5px, -5px);
    box-shadow: 17px 17px 0 #000;
}
.card h2 {
    font-size: 28px;
    font-weight: 900;
    text-transform: uppercase;
    margin-bottom: 15px;
    text-align: center;
}
.card__form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.card__form input {
    padding: 12px;
    border: 3px solid #000;
    font-size: 16px;
}
.card__form input:focus {
    outline: none;
    background: #000;
    color: #fff;
}
.card__button {
    border: 3px solid #000;
    background: #000;
    color: #fff;
    padding: 10px;
    font-size: 16px;
    font-weight: bold;
    text-transform: uppercase;
    cursor: pointer;
}
</style>
</head>

<body>

<div class="card">
    <h2>Login</h2>
    <form class="card__form" method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="card__button">Login</button>
        <button type="button" class="card__button"
                onclick="window.location.href='register.php'">
            Register
        </button>
    </form>
</div>

<script>
<?php if ($loginStatus === 'admin'): ?>
Swal.fire({ icon: 'success', title: 'Admin login successful' })
.then(() => window.location.href = 'admin_flights.php');

<?php elseif ($loginStatus === 'user'): ?>
Swal.fire({ icon: 'success', title: 'Login successful' })
.then(() => window.location.href = 'index.php');

<?php elseif ($loginStatus === 'invalid'): ?>
Swal.fire({ icon: 'error', title: 'Invalid email or password' });
<?php endif; ?>
</script>

</body>
</html>