<?php
include 'db.php';
$registerStatus = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];

    if ($name === '' || $email === '' || $pass === '') {
        $registerStatus = 'empty';
    } else {

        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $registerStatus = 'exists';
        } else {
            /* 🔐 SECURITY FIX: Hash the password cryptographically before database entry */
            $hashed_pass = password_hash($pass, PASSWORD_BCRYPT);

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')"
            );
            $stmt->bind_param("sss", $name, $email, $hashed_pass);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $registerStatus = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register</title>
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
h2 {
    text-align: center;
    font-size: 28px;
    font-weight: 900;
    text-transform: uppercase;
    margin-bottom: 20px;
}
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}
input {
    padding: 12px;
    border: 3px solid #000;
    font-size: 16px;
}
input:focus {
    outline: none;
    background: #000;
    color: #fff;
}
button {
    border: 3px solid #000;
    background: #000;
    color: #fff;
    padding: 10px;
    font-size: 16px;
    font-weight: bold;
    text-transform: uppercase;
    cursor: pointer;
}
button:active {
    transform: scale(0.95);
}
</style>
</head>

<body>

<div class="card">
    <h2>Register</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>
</div>

<script>
<?php if ($registerStatus === 'empty'): ?>
Swal.fire({ icon: 'warning', title: 'All fields are required' });
<?php elseif ($registerStatus === 'exists'): ?>
Swal.fire({ icon: 'error', title: 'Email already exists' });
<?php elseif ($registerStatus === 'error'): ?>
Swal.fire({ icon: 'error', title: 'Registration failed' });
<?php endif; ?>
</script>

</body>
</html>