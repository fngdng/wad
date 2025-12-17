<?php
// edit_marathon.php
include 'lib/db.php';
include 'lib/auth.php';
require_admin(); // Admin access only

$flash = null;
$marathonId = $_GET['id'] ?? null;

if (!$marathonId || !is_numeric($marathonId)) {
    header('Location: admin.php');
    exit;
}

// Get marathon data
$stmt = $pdo->prepare("SELECT * FROM Marathon WHERE MarathonID = ?");
$stmt->execute([$marathonId]);
$marathon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$marathon) {
    header('Location: admin.php');
    exit;
}

// PROCESS: Update marathon
if (isset($_POST['update_marathon'])) {
    $raceName = trim($_POST['race_name'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $cancelReason = trim($_POST['cancel_reason'] ?? '');

    // Validation
    $errors = [];
    if (empty($raceName)) $errors[] = 'Race name is required.';
    if (empty($date)) $errors[] = 'Date is required.';
    if (empty($status)) $errors[] = 'Status is required.';
    if ($status === 'Cancelled' && empty($cancelReason)) {
        $errors[] = 'Cancel reason is required when status is Cancelled.';
    }

    if (count($errors) > 0) {
        $flash = ['type' => 'error', 'text' => implode(' ', $errors)];
    } else {
        try {
            // If status is not Cancelled, clear cancel reason
            if ($status !== 'Cancelled') {
                $cancelReason = null;
            }

            $stmt = $pdo->prepare("UPDATE Marathon SET RaceName = ?, Date = ?, Status = ?, CancelReason = ? WHERE MarathonID = ?");
            $stmt->execute([$raceName, $date, $status, $cancelReason, $marathonId]);
            $flash = ['type' => 'success', 'text' => 'Marathon updated successfully!'];
            
            // Refresh marathon data
            $stmt = $pdo->prepare("SELECT * FROM Marathon WHERE MarathonID = ?");
            $stmt->execute([$marathonId]);
            $marathon = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $flash = ['type' => 'error', 'text' => 'Error updating marathon: ' . $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Marathon | Hanoi Marathon</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .marathon-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .marathon-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .marathon-header h1 {
            font-size: 28px;
            color: #fff;
            margin-bottom: 10px;
        }

        .marathon-header p {
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

        #cancelReasonGroup {
            display: none;
        }

        #cancelReasonGroup.show {
            display: block;
        }

        @media (max-width: 600px) {
            .marathon-container {
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
    <script>
        function toggleCancelReason() {
            const status = document.getElementById('status').value;
            const cancelGroup = document.getElementById('cancelReasonGroup');
            const cancelInput = document.getElementById('cancel_reason');
            
            if (status === 'Cancelled') {
                cancelGroup.classList.add('show');
                cancelInput.setAttribute('required', 'required');
            } else {
                cancelGroup.classList.remove('show');
                cancelInput.removeAttribute('required');
            }
        }
        
        // Initialize on page load
        window.addEventListener('DOMContentLoaded', function() {
            toggleCancelReason();
        });
    </script>
</head>
<body>
    <div class="marathon-container">
        <div class="marathon-header">
            <h1>Edit Marathon</h1>
            <p>Update race information</p>
        </div>

        <?php if ($flash): ?>
            <div class="flash-message flash-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['text']); ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            🏃 Marathon ID: <strong><?php echo htmlspecialchars($marathon['MarathonID']); ?></strong>
        </div>

        <?php
        // Check if there are participants
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Participate WHERE MarathonID = ?");
        $stmt->execute([$marathonId]);
        $participantCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($participantCount > 0):
        ?>
            <div class="warning-box">
                ⚠️ This race has <strong><?php echo $participantCount; ?></strong> registered participant(s). Changes will affect their records.
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="race_name">Race Name *</label>
                <input type="text" id="race_name" name="race_name" value="<?php echo htmlspecialchars($marathon['RaceName'] ?? ''); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="date">Race Date *</label>
                    <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($marathon['Date'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required onchange="toggleCancelReason()">
                        <option value="">-- Select Status --</option>
                        <option value="Scheduled" <?php echo ($marathon['Status'] === 'Scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="Cancelled" <?php echo ($marathon['Status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="form-group" id="cancelReasonGroup">
                <label for="cancel_reason">Cancel Reason</label>
                <textarea id="cancel_reason" name="cancel_reason" placeholder="Explain why this race is cancelled"><?php echo htmlspecialchars($marathon['CancelReason'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="update_marathon" class="btn btn-save">💾 Save Changes</button>
                <a href="admin.php" class="btn btn-cancel">❌ Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
