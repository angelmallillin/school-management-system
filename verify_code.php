<?php
session_start();
include 'db_connection.php';

// Check if a user is in the verification process
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['temp_user_id'];
    $entered_code = $_POST['verification_code'];

    // Fetch the stored code from the database
    $stmt = $conn->prepare("SELECT verification_code, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && $entered_code === $user['verification_code']) {
        // Code is correct, log the user in
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = $user['role'];
        
        // Fetch the user's username based on their ID
        $stmt_username = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt_username->bind_param("i", $user_id);
        $stmt_username->execute();
        $username_result = $stmt_username->get_result();
        $user_data = $username_result->fetch_assoc();
        $_SESSION['username'] = $user_data['username'];
        $stmt_username->close();

        // Clear the temporary session data and verification code from the database
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_role']);
        $stmt_clear_code = $conn->prepare("UPDATE users SET verification_code = NULL WHERE id = ?");
        $stmt_clear_code->bind_param("i", $user_id);
        $stmt_clear_code->execute();
        $stmt_clear_code->close();

        // Redirect to the appropriate dashboard
        header("Location: index.php");
        exit;

    } else {
        $message = "Invalid verification code. Please try again.";
        $message_type = 'danger';
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-verify {
            background-color: #0d6efd;
            border-color: #0d6efd;
            font-weight: 600;
            padding: 10px;
            border-radius: 5px;
        }
        .btn-verify:hover {
            background-color: #0b5ed7;
            border-color: #0b5ed7;
        }
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1 class="display-4 fw-bold text-primary">SMS</h1>
        </div>
        <h2 class="text-center">Email Verification</h2>
        <p class="text-center text-muted">A verification code has been sent to your email address. Please check your inbox and enter the code below.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> text-center" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="verify_code.php" method="post">
            <div class="mb-3">
                <label for="verification_code" class="form-label">Verification Code</label>
                <input type="text" class="form-control" id="verification_code" name="verification_code" required maxlength="6" pattern="\d{6}">
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-verify text-white">Verify Code</button>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>