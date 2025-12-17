<?php
// edit_participant.php
include 'lib/db.php';
include 'lib/auth.php';
require_admin(); // Admin access only

$flash = null;
$userId = $_GET['id'] ?? null;

if (!$userId || !is_numeric($userId)) {
    header('Location: admin.php');
    exit;
}

// Get participant data
$stmt = $pdo->prepare("SELECT * FROM Participant WHERE UserID = ?");
$stmt->execute([$userId]);
$participant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$participant) {
    header('Location: admin.php');
    exit;
}

// PROCESS: Update participant
if (isset($_POST['update_participant'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $passportNo = trim($_POST['passport_no'] ?? '');
    $bestRecord = trim($_POST['best_record'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role = trim($_POST['role'] ?? 'user');
    $newPassword = trim($_POST['new_password'] ?? '');

    // Validation
    $errors = [];
    if (empty($name)) $errors[] = 'Full name is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
    if (empty($phone)) $errors[] = 'Phone is required.';
    if (empty($age) || !is_numeric($age) || $age < 1 || $age > 120) $errors[] = 'Age must be between 1-120.';
    if (empty($sex)) $errors[] = 'Gender is required.';
    if (!in_array($role, ['admin', 'user'])) $errors[] = 'Invalid role.';
    if (!empty($newPassword) && strlen($newPassword) < 6) $errors[] = 'Password must be at least 6 characters.';

    if (count($errors) > 0) {
        $flash = ['type' => 'error', 'text' => implode(' ', $errors)];
    } else {
        try {
            // Update participant info
            $stmt = $pdo->prepare("UPDATE Participant SET Name = ?, Email = ?, Phone = ?, Age = ?, Sex = ?, Nationality = ?, PassportNO = ?, BestRecord = ?, Address = ?, Role = ? WHERE UserID = ?");
            $stmt->execute([$name, $email, $phone, $age, $sex, $nationality, $passportNo, $bestRecord, $address, $role, $userId]);
            
            // Update password if provided
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE Participant SET Password = ? WHERE UserID = ?");
                $stmt->execute([$hashedPassword, $userId]);
                $flash = ['type' => 'success', 'text' => 'Participant updated successfully! Password has been reset.'];
            } else {
                $flash = ['type' => 'success', 'text' => 'Participant updated successfully!'];
            }
            
            // Refresh participant data
            $stmt = $pdo->prepare("SELECT * FROM Participant WHERE UserID = ?");
            $stmt->execute([$userId]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $flash = ['type' => 'error', 'text' => 'Error updating participant: ' . $e->getMessage()];
        }
    }
}

// Check race participation count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Participate WHERE UserID = ?");
$stmt->execute([$userId]);
$raceCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Participant | Hanoi Marathon</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .participant-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .participant-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .participant-header h1 {
            font-size: 28px;
            color: #fff;
            margin-bottom: 10px;
        }

        .participant-header p {
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

        .form-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #334155;
        }

        .form-section h3 {
            color: #e2e8f0;
            font-size: 16px;
            margin-bottom: 15px;
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
            text-decoration: none;
            text-align: center;
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

        .warning-box {
            background: rgba(245, 158, 11, 0.1);
            border-left: 3px solid #f59e0b;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #fcd34d;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: rgba(59, 130, 246, 0.1);
            padding: 10px;
            border-radius: 6px;
            text-align: center;
        }

        .stat-label {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: #60a5fa;
        }

        .password-note {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 5px;
            font-style: italic;
        }

        @media (max-width: 600px) {
            .participant-container {
                margin: 20px;
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="participant-container">
        <div class="participant-header">
            <h1>Edit Participant</h1>
            <p>Update user information and permissions</p>
        </div>

        <?php if ($flash): ?>
            <div class="flash-message flash-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['text']); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">User ID</div>
                <div class="stat-value">#<?php echo htmlspecialchars($participant['UserID']); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Current Role</div>
                <div class="stat-value"><?php echo strtoupper(htmlspecialchars($participant['Role'])); ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Races Joined</div>
                <div class="stat-value"><?php echo $raceCount; ?></div>
            </div>
        </div>

        <div class="info-box">
            👤 Username: <strong><?php echo htmlspecialchars($participant['Username']); ?></strong> (cannot be changed)
        </div>

        <?php if ($raceCount > 0): ?>
            <div class="warning-box">
                ⚠️ This user has registered for <strong><?php echo $raceCount; ?></strong> race(s). Changes to personal information will be reflected in their race records.
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($participant['Name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="age">Age *</label>
                    <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($participant['Age'] ?? ''); ?>" min="1" max="120" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sex">Gender *</label>
                    <select id="sex" name="sex" required>
                        <option value="">-- Select Gender --</option>
                        <option value="Male" <?php echo ($participant['Sex'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($participant['Sex'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($participant['Sex'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($participant['Email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone *</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($participant['Phone'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="nationality">Nationality</label>
                    <input type="text" id="nationality" name="nationality" value="<?php echo htmlspecialchars($participant['Nationality'] ?? ''); ?>" placeholder="e.g., Vietnamese">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="passport_no">Passport No.</label>
                    <input type="text" id="passport_no" name="passport_no" value="<?php echo htmlspecialchars($participant['PassportNO'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="best_record">Best Record (Time)</label>
                    <input type="text" id="best_record" name="best_record" value="<?php echo htmlspecialchars($participant['BestRecord'] ?? ''); ?>" placeholder="e.g., 2:30:45">
                </div>
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" placeholder="Full address"><?php echo htmlspecialchars($participant['Address'] ?? ''); ?></textarea>
            </div>

            <div class="form-section">
                <h3>🔐 Security & Permissions</h3>
                
                <div class="form-group">
                    <label for="role">User Role *</label>
                    <select id="role" name="role" required>
                        <option value="user" <?php echo ($participant['Role'] === 'user') ? 'selected' : ''; ?>>User (Regular participant)</option>
                        <option value="admin" <?php echo ($participant['Role'] === 'admin') ? 'selected' : ''; ?>>Admin (Full access)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="new_password">Reset Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password" minlength="6">
                    <div class="password-note">💡 Only fill this field if you want to reset the user's password (min 6 characters)</div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="update_participant" class="btn btn-save">💾 Save Changes</button>
                <a href="admin.php" class="btn btn-cancel">❌ Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
