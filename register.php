<?php
include 'lib/db.php';

session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = false;
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $bestRecord = trim($_POST['best_record'] ?? '');
    $nationality = trim($_POST['n'] ?? '');
    $passportNO = trim($_POST['passport'] ?? '');
    $sex = $_POST['s'] ?? '';
    $age = (int)($_POST['a'] ?? 0);
    $email = trim($_POST['e'] ?? '');
    $phone = trim($_POST['p'] ?? '');
    $address = trim($_POST['ad'] ?? '');

    // Preserve form data for re-display
    $formData = compact('username', 'name', 'bestRecord', 'nationality', 'passportNO', 'sex', 'age', 'email', 'phone', 'address');

    // Validate required fields
    if ($username === '') {
        $errors[] = '⚠️ Please enter Username.';
    } elseif (strlen($username) < 3) {
        $errors[] = '⚠️ Username must be at least 3 characters.';
    }
    
    if ($password === '') {
        $errors[] = '⚠️ Please enter Password.';
    } elseif (strlen($password) < 6) {
        $errors[] = '⚠️ Password must be at least 6 characters.';
    }
    
    if ($name === '') {
        $errors[] = '⚠️ Please enter Full Name.';
    }
    
    // Validate required Sex
    if ($sex === '' || $sex === '--Select--') {
        $errors[] = '⚠️ Please select Gender.';
    }
    
    // Validate required Age
    if ($age <= 0) {
        $errors[] = '⚠️ Please enter Age (1-120).';
    } elseif ($age > 120) {
        $errors[] = '⚠️ Invalid Age (1-120).';
    }
    
    // Validate required Email
    if ($email === '') {
        $errors[] = '⚠️ Please enter Email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '⚠️ Invalid email format. Please check again.';
    }
    
    // Validate required Phone
    if ($phone === '') {
        $errors[] = '⚠️ Please enter Phone Number.';
    }
    
    // Check username exists only if no validation errors
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT UserID FROM Participant WHERE Username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = '⚠️ Username "' . htmlspecialchars($username) . '" already exists. Please choose another.';
        }
    }

    if (!$errors) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO Participant (Username, Password, Name, BestRecord, Nationality, PassportNO, Sex, Age, Email, Phone, Address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashed, $name, $bestRecord, $nationality, $passportNO, $sex, $age ?: null, $email, $phone, $address]);
            $success = true;
        } catch (Exception $e) {
            $errors[] = 'Username already exists or system error occurred.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Registration</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="nav">
        <a href="index.php">Home</a>
        <strong>Register</strong>
    </div>

    <div class="card narrow">
        <h2>Create Account</h2>
        <p class="muted">Enter accurate information for organizer contact.</p>

        <?php if ($errors): ?>
            <div class="alert error">
                <?php foreach ($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success">Registration successful! <a href="login.php">Login now</a></div>
        <?php else: ?>
            <form method="POST" class="form-grid">
                <label>Username* <span class="muted">(at least 3 chars)</span>
                    <input type="text" name="username" required minlength="3" maxlength="50" value="<?= htmlspecialchars($formData['username'] ?? '') ?>">
                </label>
                <label>Password* <span class="muted">(at least 6 chars)</span>
                    <input type="password" name="password" required minlength="6">
                </label>
                <label>Full Name*
                    <input type="text" name="name" required maxlength="100" value="<?= htmlspecialchars($formData['name'] ?? '') ?>">
                </label>
                <label>Best Record <span class="muted">(e.g. 03:45:22)</span>
                    <input type="text" name="best_record" placeholder="HH:MM:SS" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}" value="<?= htmlspecialchars($formData['bestRecord'] ?? '') ?>">
                </label>
                <label>Nationality
                    <input type="text" name="n" maxlength="50" value="<?= htmlspecialchars($formData['nationality'] ?? '') ?>">
                </label>
                <label>Passport No.
                    <input type="text" name="passport" maxlength="50" value="<?= htmlspecialchars($formData['passportNO'] ?? '') ?>">
                </label>
                <label>Gender*
                    <select name="s" required>
                        <option value="">--Select--</option>
                        <option value="Male" <?= ($formData['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($formData['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </label>
                <label>Age* <span class="muted">(1-120)</span>
                    <input type="number" name="a" min="1" max="120" required value="<?= ($formData['age'] ?? 0) ?: '' ?>">
                </label>
                <label>Email*
                    <input type="email" name="e" maxlength="100" required value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                </label>
                <label>Phone Number*
                    <input type="text" name="p" maxlength="20" required value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                </label>
                <label>Address
                    <textarea name="ad" rows="2"><?= htmlspecialchars($formData['address'] ?? '') ?></textarea>
                </label>
                <button type="submit" class="btn primary full">Register</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>