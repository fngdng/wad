<?php
include 'lib/db.php';

// Prevent logged-in users from hitting login again
session_start();
if (isset($_SESSION['user_id'])) {
	header('Location: index.php');
	exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim($_POST['username'] ?? '');
	$password = $_POST['password'] ?? '';

	if ($username === '' || $password === '') {
		$errors[] = 'Please enter both Username and Password.';
	}

	if (!$errors) {
		$stmt = $pdo->prepare('SELECT * FROM Participant WHERE Username = ? LIMIT 1');
		$stmt->execute([$username]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		$isValidPassword = false;
		if ($user) {
			// Debug: Log password verification attempt
			$verified = password_verify($password, $user['Password']);
			error_log("Login attempt - User: $username | Hash verify: " . ($verified ? 'YES' : 'NO') . " | Password len: " . strlen($password));
			
			// Prefer hashed verification, but keep plain-text fallback for legacy rows
			$isValidPassword = $verified || $user['Password'] === $password;
		} else {
			error_log("Login attempt - User not found: $username");
		}

		if ($user && $isValidPassword) {
			$_SESSION['user_id'] = $user['UserID'];
			$_SESSION['role'] = $user['Role'] ?? 'user';
			$_SESSION['name'] = $user['Name'] ?: $user['Username'];
			header('Location: index.php');
			exit();
		} else {
			$errors[] = 'Incorrect username or password.';
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Login - Hanoi Marathon System</title>
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
	<div class="nav">
		<a href="index.php">Home</a>
		<strong>Login</strong>
	</div>

	<div class="card narrow">
		<h2>Welcome Back</h2>
		<p class="muted">Login to register for races or manage the system.</p>
		<?php if ($errors): ?>
			<div class="alert error">
				<?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
			</div>
		<?php endif; ?>
		<form method="POST" class="form-grid">
			<label>Username
				<input type="text" name="username" required>
			</label>
			<label>Password
				<input type="password" name="password" required>
			</label>
			<button type="submit" class="btn primary full">Login</button>
		</form>
		<p class="muted">Don't have an account? <a href="register.php">Register now</a></p>
	</div>
</body>
</html>
