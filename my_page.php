<?php
// my_page.php
include 'lib/db.php';
include 'lib/auth.php';
require_login();
$uid = $_SESSION['user_id'];
$flash = null;

// PROCESS: Register for marathon
if (isset($_POST['join_marathon'])) {
    $stmt = $pdo->prepare("SELECT MarathonID, RaceName, Date, Status FROM Marathon WHERE MarathonID = ?");
    $stmt->execute([$_POST['mid']]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$race || $race['Status'] !== 'Scheduled') {
        $flash = ['type' => 'error', 'text' => 'Invalid race or already cancelled.'];
    } else {
        $raceDate = new DateTime($race['Date']);
        $today = new DateTime('today');
        if ($raceDate < $today) {
            $flash = ['type' => 'error', 'text' => 'Race already passed, cannot register.'];
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO Participate (MarathonID, UserID) VALUES (?, ?)");
                $stmt->execute([$_POST['mid'], $uid]);
                $flash = ['type' => 'success', 'text' => 'Successfully registered! Waiting for Admin to assign BIB number.'];
            } catch (Exception $e) {
                $flash = ['type' => 'error', 'text' => 'Already registered or invalid information.'];
            }
        }
    }
}

// PROCESS: Cancel participation (IMPORTANT: Only delete from Participate table, do NOT delete user)
if (isset($_POST['cancel_join'])) {
    $stmt = $pdo->prepare("SELECT Date, Status FROM Marathon WHERE MarathonID = ?");
    $stmt->execute([$_POST['mid']]);
    $race = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($race) {
        $raceDate = new DateTime($race['Date']);
        $today = new DateTime('today');
        if ($race['Status'] !== 'Scheduled' || $raceDate <= $today) {
            $flash = ['type' => 'error', 'text' => 'Cannot cancel - race is running/past or cancelled. History preserved.'];
        } else {
            $stmt = $pdo->prepare("DELETE FROM Participate WHERE MarathonID = ? AND UserID = ?");
            $stmt->execute([$_POST['mid'], $uid]);
            $flash = ['type' => 'success', 'text' => 'Registration cancelled. Personal data remains safe.'];
        }
    }
}

// Get list of OPEN marathons (Not cancelled)
$marathons = $pdo->query("SELECT * FROM Marathon WHERE Status = 'Scheduled' AND Date >= CURDATE() ORDER BY Date ASC")->fetchAll();

// Get my participation history
$my_history = $pdo->prepare("SELECT P.*, M.RaceName, M.Date, M.Status as RaceStatus, M.CancelReason
                             FROM Participate P 
                             JOIN Marathon M ON P.MarathonID = M.MarathonID 
                             WHERE P.UserID = ?");
$my_history->execute([$uid]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/style.css">
    <title>My Page</title>
</head>
<body>
    <div class="nav">
        <a href="index.php">Home</a>
        <strong>My Page</strong>
        <a href="edit_profile.php">Edit Profile</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="page-grid">
        <div class="card">
            <h3>1. Register for New Marathon</h3>
            <p class="muted">Only showing scheduled and upcoming races.</p>
            <?php if ($flash): ?>
                <div class="alert <?= $flash['type'] ?>"><?= htmlspecialchars($flash['text']) ?></div>
            <?php endif; ?>
            <form method="POST" class="form-grid">
                <label>Select Race
                    <select name="mid" required>
                        <?php foreach($marathons as $m): ?>
                            <option value="<?= $m['MarathonID'] ?>"><?= $m['RaceName'] ?> (<?= $m['Date'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" name="join_marathon" class="btn primary">Register Now</button>
            </form>
        </div>

        <div class="card">
            <h3>2. My Participation History</h3>
            <table class="data-table">
                <tr><th>Race</th><th>Date</th><th>BIB No.</th><th>Time</th><th>Race Status</th><th>Cancel Reason</th><th>Action</th></tr>
                <?php while($row = $my_history->fetch()): ?>
                <tr>
                    <td><?= $row['RaceName'] ?></td>
                    <td><?= $row['Date'] ?></td>
                    <td><?= $row['EntryNO'] ? $row['EntryNO'] : 'Pending...' ?></td>
                    <td><?= $row['TimeRecord'] ?: '-' ?></td>
                    <td><span class="tag <?= strtolower($row['RaceStatus']) ?>"><?= $row['RaceStatus'] ?></span></td>
                    <td><?= htmlspecialchars($row['CancelReason'] ?? '-') ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Cancel this registration? Your personal data will remain safe.');">
                            <input type="hidden" name="mid" value="<?= $row['MarathonID'] ?>">
                            <button type="submit" name="cancel_join" class="btn ghost">Cancel Registration</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>