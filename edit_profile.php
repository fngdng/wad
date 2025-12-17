<?php
// edit_profile.php
include 'lib/db.php';
include 'lib/auth.php';
require_login();

$uid = $_SESSION['user_id'];
$flash = null;

// Get current user info
$stmt = $pdo->prepare("SELECT * FROM Participant WHERE UserID = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// PROCESS: Update profile
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $passportNo = trim($_POST['passport_no'] ?? '');
    $bestRecord = trim($_POST['best_record'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $sex = trim($_POST['sex'] ?? '');

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Full name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty($phone)) $errors[] = 'Phone is required.';
    if (empty($age) || !is_numeric($age) || $age < 1 || $age > 120) $errors[] = 'Age must be between 1-120.';
    if (empty($sex)) $errors[] = 'Gender is required.';

    if (count($errors) > 0) {
        $flash = ['type' => 'error', 'text' => implode(' ', $errors)];
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE Participant SET Name = ?, Email = ?, Phone = ?, Age = ?, Nationality = ?, PassportNO = ?, BestRecord = ?, Address = ?, Sex = ? WHERE UserID = ?");
            $stmt->execute([$name, $email, $phone, $age, $nationality, $passportNo, $bestRecord, $address, $sex, $uid]);
            $flash = ['type' => 'success', 'text' => 'Profile updated successfully!'];
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM Participant WHERE UserID = ?");
            $stmt->execute([$uid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $flash = ['type' => 'error', 'text' => 'Error updating profile: ' . $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Hanoi Marathon</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-header h1 {
            font-size: 28px;
            color: #fff;
            margin-bottom: 10px;
        }

        .profile-header p {
            color: #94a3b8;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #e2e8f0;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 8px;
            color: #fff;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(30, 41, 59, 0.95);
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Space Grotesk', sans-serif;
        }

        .btn-save {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }

        .btn-cancel {
            background: rgba(148, 163, 184, 0.1);
            color: #cbd5e1;
            border: 1px solid #475569;
        }

        .btn-cancel:hover {
            background: rgba(148, 163, 184, 0.2);
        }

        .flash-message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .flash-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid #22c55e;
            color: #86efac;
        }

        .flash-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border-left: 3px solid #3b82f6;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #bfdbfe;
        }

        @media (max-width: 600px) {
            .profile-container {
                margin: 20px;
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Edit Profile</h1>
            <p>Update your personal information</p>
        </div>

        <?php if ($flash): ?>
            <div class="flash-message flash-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['text']); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            👤 Username: <strong><?php echo htmlspecialchars($user['Username']); ?></strong> (cannot be changed)
        </div>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['Name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="age">Age *</label>
                    <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($user['Age'] ?? ''); ?>" min="1" max="120" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sex">Gender *</label>
                    <select id="sex" name="sex" required>
                        <option value="">-- Select Gender --</option>
                        <option value="Male" <?php echo ($user['Sex'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($user['Sex'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($user['Sex'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['Email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone *</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['Phone'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="nationality">Nationality</label>
                    <input type="text" id="nationality" name="nationality" value="<?php echo htmlspecialchars($user['Nationality'] ?? ''); ?>" placeholder="e.g., Vietnamese">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="passport_no">Passport No.</label>
                    <input type="text" id="passport_no" name="passport_no" value="<?php echo htmlspecialchars($user['PassportNO'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="best_record">Best Record (Time)</label>
                    <input type="text" id="best_record" name="best_record" value="<?php echo htmlspecialchars($user['BestRecord'] ?? ''); ?>" placeholder="e.g., 2:30:45">
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" placeholder="Your full address"><?php echo htmlspecialchars($user['Address'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="update_profile" class="btn btn-save">💾 Save Changes</button>
                <a href="my_page.php" class="btn btn-cancel" style="text-decoration: none; text-align: center;">❌ Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
