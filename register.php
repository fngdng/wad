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
    if ($age < 16) {
        $errors[] = '⚠️ Age must be at least 16.';
    } elseif ($age > 69) {
        $errors[] = '⚠️ Age must be 69 or below.';
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
    } elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $errors[] = '⚠️ Invalid phone format. Use digits, +, - and spaces (7-20 chars).';
    }

    // Validate required Best Record (HH:MM:SS)
    if ($bestRecord === '') {
        $errors[] = '⚠️ Please enter Best Record time.';
    } elseif (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $bestRecord)) {
        $errors[] = '⚠️ Best Record must be in HH:MM:SS format.';
    }

    // Validate required Nationality
    if ($nationality === '') {
        $errors[] = '⚠️ Please select Nationality.';
    }

    // Validate required Passport No. (alphanumeric 5-20)
    if ($passportNO === '') {
        $errors[] = '⚠️ Please enter Passport No.';
    } elseif (!preg_match('/^[A-Za-z0-9]{5,20}$/', $passportNO)) {
        $errors[] = '⚠️ Passport No. must be alphanumeric (5-20 chars).';
    }

    // Validate required Address (min length)
    if ($address === '') {
        $errors[] = '⚠️ Please enter Address.';
    } elseif (strlen($address) < 5) {
        $errors[] = '⚠️ Address is too short.';
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
                <label>Best Record* <span class="muted">(e.g. 03:45:22)</span>
                    <input type="text" name="best_record" placeholder="HH:MM:SS" required pattern="\d{2}:\d{2}:\d{2}" value="<?= htmlspecialchars($formData['bestRecord'] ?? '') ?>">
                </label>
                <label>Nationality*
                    <select name="n" required>
                        <option value="">--Select Country--</option>
                        <option value="Vietnam" <?= ($formData['nationality'] ?? '') === 'Vietnam' ? 'selected' : '' ?>>Vietnam</option>
                        <option value="United States" <?= ($formData['nationality'] ?? '') === 'United States' ? 'selected' : '' ?>>United States</option>
                        <option value="United Kingdom" <?= ($formData['nationality'] ?? '') === 'United Kingdom' ? 'selected' : '' ?>>United Kingdom</option>
                        <option value="Japan" <?= ($formData['nationality'] ?? '') === 'Japan' ? 'selected' : '' ?>>Japan</option>
                        <option value="South Korea" <?= ($formData['nationality'] ?? '') === 'South Korea' ? 'selected' : '' ?>>South Korea</option>
                        <option value="China" <?= ($formData['nationality'] ?? '') === 'China' ? 'selected' : '' ?>>China</option>
                        <option value="Thailand" <?= ($formData['nationality'] ?? '') === 'Thailand' ? 'selected' : '' ?>>Thailand</option>
                        <option value="Singapore" <?= ($formData['nationality'] ?? '') === 'Singapore' ? 'selected' : '' ?>>Singapore</option>
                        <option value="Malaysia" <?= ($formData['nationality'] ?? '') === 'Malaysia' ? 'selected' : '' ?>>Malaysia</option>
                        <option value="Indonesia" <?= ($formData['nationality'] ?? '') === 'Indonesia' ? 'selected' : '' ?>>Indonesia</option>
                        <option value="Philippines" <?= ($formData['nationality'] ?? '') === 'Philippines' ? 'selected' : '' ?>>Philippines</option>
                        <option value="Australia" <?= ($formData['nationality'] ?? '') === 'Australia' ? 'selected' : '' ?>>Australia</option>
                        <option value="Canada" <?= ($formData['nationality'] ?? '') === 'Canada' ? 'selected' : '' ?>>Canada</option>
                        <option value="France" <?= ($formData['nationality'] ?? '') === 'France' ? 'selected' : '' ?>>France</option>
                        <option value="Germany" <?= ($formData['nationality'] ?? '') === 'Germany' ? 'selected' : '' ?>>Germany</option>
                        <option value="India" <?= ($formData['nationality'] ?? '') === 'India' ? 'selected' : '' ?>>India</option>
                        <option value="Other" <?= ($formData['nationality'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </label>
                <label>Passport No.*
                    <input type="text" name="passport" maxlength="50" required pattern="[A-Za-z0-9]{5,20}" value="<?= htmlspecialchars($formData['passportNO'] ?? '') ?>">
                </label>
                <label>Gender*
                    <select name="s" required>
                        <option value="">--Select--</option>
                        <option value="Male" <?= ($formData['sex'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($formData['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= ($formData['sex'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </label>
                <label>Age* <span class="muted">(16-69)</span>
                    <input type="number" name="a" min="16" max="69" required value="<?= ($formData['age'] ?? 0) ?: '' ?>">
                </label>
                <label>Email*
                    <input type="email" name="e" maxlength="100" required value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                </label>
                <label>Phone Number*
                    <input type="text" name="p" maxlength="20" required pattern="[0-9+\-\s]{7,20}" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                </label>
                <label>Address*
                    <textarea name="ad" rows="2" required minlength="5"><?= htmlspecialchars($formData['address'] ?? '') ?></textarea>
                </label>
                <button type="submit" class="btn primary full">Register</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>