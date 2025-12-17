<?php
// admin.php
include 'lib/db.php';
include 'lib/auth.php';
require_admin(); // Admin access only
$flash = null;

// HANDLE 0: Create new marathon (CRUD Create)
if (isset($_POST['create_marathon'])) {
    $name = trim($_POST['race_name'] ?? '');
    $date = $_POST['date'] ?? '';
    if ($name === '' || $date === '') {
        $flash = ['type' => 'error', 'text' => 'Please enter race name and date.'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO Marathon (RaceName, Date, Status) VALUES (?, ?, 'Scheduled')");
        $stmt->execute([$name, $date]);
        $flash = ['type' => 'success', 'text' => 'New race created successfully.'];
    }
}

// HANDLE 1: Assign Entry Number to participant
if (isset($_POST['set_entry'])) {
    $stmt = $pdo->prepare("UPDATE Participate SET EntryNO = ? WHERE MarathonID = ? AND UserID = ?");
    $stmt->execute([$_POST['entry_no'], $_POST['mid'], $_POST['uid']]);
    $flash = ['type' => 'success', 'text' => 'BIB number saved for participant.'];
}

// HANDLE 2: Update race results (CRUD Update)
if (isset($_POST['set_result'])) {
    $stmt = $pdo->prepare("UPDATE Participate SET TimeRecord = ?, Standings = ? WHERE MarathonID = ? AND UserID = ?");
    $stmt->execute([$_POST['time'], $_POST['rank'], $_POST['mid'], $_POST['uid']]);
    $flash = ['type' => 'success', 'text' => 'Results saved for participant.'];
}

// HANDLE 3: Cancel marathon (Soft Delete - MOST IMPORTANT)
// Don't use DELETE to preserve historical data
if (isset($_POST['cancel_marathon'])) {
    $reason = trim($_POST['cancel_reason'] ?? 'Cancelled by admin');
    $stmt = $pdo->prepare("UPDATE Marathon SET Status = 'Cancelled', CancelReason = ? WHERE MarathonID = ?");
    $stmt->execute([$reason, $_POST['mid']]);
    $flash = ['type' => 'success', 'text' => 'Race cancelled (Soft Delete). Reason recorded. Historical data preserved.'];
}

// HANDLE 4: Delete participant (PERMANENT - Use with caution)
if (isset($_POST['delete_participant'])) {
    $userId = $_POST['uid'];
    
    // Check if user has race history
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Participate WHERE UserID = ?");
    $stmt->execute([$userId]);
    $raceCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($raceCount > 0) {
        // Delete all race registrations first (to avoid foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM Participate WHERE UserID = ?");
        $stmt->execute([$userId]);
    }
    
    // Delete the participant
    $stmt = $pdo->prepare("DELETE FROM Participant WHERE UserID = ?");
    $stmt->execute([$userId]);
    
    if ($raceCount > 0) {
        $flash = ['type' => 'success', 'text' => "Participant deleted. Warning: {$raceCount} race registration(s) were also removed from history."];
    } else {
        $flash = ['type' => 'success', 'text' => 'Participant deleted successfully.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><link rel="stylesheet" href="css/style.css"></head>
<body>
    <div class="nav"><a href="index.php">Home</a> | <strong>Admin Panel</strong></div>
    <h2>Administrator Dashboard</h2>
    <?php if($flash): ?>
        <div class="alert <?= $flash['type'] ?>"><?= htmlspecialchars($flash['text']) ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>0. Create New Marathon</h3>
        <form method="POST" class="form-inline">
            <input type="text" name="race_name" placeholder="Race Name" required>
            <input type="date" name="date" required>
            <button type="submit" name="create_marathon" class="btn primary">Add Race</button>
        </form>
    </div>

    <h3>1. Marathon Management</h3>
    <?php 
    $races = $pdo->query("SELECT * FROM Marathon")->fetchAll();
    ?>
    <table class="data-table">
        <tr><th>ID</th><th>Race Name</th><th>Date</th><th>Status</th><th>Cancel Reason</th><th>Actions</th></tr>
        <?php foreach($races as $r): ?>
        <tr>
            <td><?= $r['MarathonID'] ?></td>
            <td><?= $r['RaceName'] ?></td>
            <td><?= $r['Date'] ?></td>
            <td style="color: <?= $r['Status']=='Cancelled'?'red':'green' ?>"><?= $r['Status'] ?></td>
            <td><?= htmlspecialchars($r['CancelReason'] ?? '-') ?></td>
            <td>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <a href="edit_marathon.php?id=<?= $r['MarathonID'] ?>" class="btn ghost" style="text-decoration: none; padding: 8px 12px; white-space: nowrap;">✏️ Edit</a>
                    <?php if($r['Status'] != 'Cancelled'): ?>
                    <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                        <input type="hidden" name="mid" value="<?= $r['MarathonID'] ?>">
                        <input type="text" name="cancel_reason" placeholder="Reason (COVID, Weather, etc.)" required style="padding: 8px; border-radius: 6px; border: 1px solid var(--line); background: rgba(255,255,255,0.03); color: var(--text); min-width: 150px;">
                        <button type="submit" name="cancel_marathon" style="color:red; white-space: nowrap;" onclick="return confirm('Confirm cancellation?');">Cancel Race</button>
                    </form>
                    <?php else: ?>
                        <span style="color: #94a3b8;">Cancelled</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>2. Participant & Results Management</h3>
    <?php
    // Join 3 tables for full display
    $sql = "SELECT P.*, U.Name, U.Username, M.RaceName 
            FROM Participate P 
            JOIN Participant U ON P.UserID = U.UserID 
            JOIN Marathon M ON P.MarathonID = M.MarathonID 
            ORDER BY M.Date DESC";
    $list = $pdo->query($sql)->fetchAll();
    ?>
    <table class="data-table">
        <tr><th>Race</th><th>Participant</th><th>BIB No.</th><th>Results (Time/Rank)</th><th>Update</th></tr>
        <?php foreach($list as $l): ?>
        <tr>
            <td><?= $l['RaceName'] ?></td>
            <td><?= $l['Name'] ?> (<?= $l['Username'] ?>)</td>
            
            <form method="POST">
                <input type="hidden" name="mid" value="<?= $l['MarathonID'] ?>">
                <input type="hidden" name="uid" value="<?= $l['UserID'] ?>">
                
                <td><input type="text" name="entry_no" value="<?= $l['EntryNO'] ?>" placeholder="Assign BIB" size="10"></td>
                <td>
                    Time: <input type="text" name="time" value="<?= $l['TimeRecord'] ?>" size="8" placeholder="HH:MM:SS">
                    Rank: <input type="number" name="rank" value="<?= $l['Standings'] ?>" style="width:50px">
                </td>
                <td>
                    <button type="submit" name="set_entry" class="btn">Save BIB</button>
                    <button type="submit" name="set_result" class="btn primary">Save Result</button>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>3. Participant Management</h3>
    <table class="data-table">
        <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Email</th><th>Phone</th><th>Races</th><th>Actions</th></tr>
        <?php 
        $participants = $pdo->query("SELECT p.*, COUNT(pt.MarathonID) as race_count 
                                      FROM Participant p 
                                      LEFT JOIN Participate pt ON p.UserID = pt.UserID 
                                      GROUP BY p.UserID 
                                      ORDER BY p.UserID DESC")->fetchAll();
        foreach($participants as $u): 
        ?>
            <tr>
                <td><?= $u['UserID'] ?></td>
                <td><?= htmlspecialchars($u['Username']) ?></td>
                <td><?= htmlspecialchars($u['Name']) ?></td>
                <td><span class="tag <?= $u['Role'] === 'admin' ? 'scheduled' : '' ?>"><?= strtoupper($u['Role']) ?></span></td>
                <td><?= htmlspecialchars($u['Email']) ?></td>
                <td><?= htmlspecialchars($u['Phone']) ?></td>
                <td style="text-align: center;"><?= $u['race_count'] ?></td>
                <td>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <a href="edit_participant.php?id=<?= $u['UserID'] ?>" class="btn ghost" style="text-decoration: none; padding: 8px 12px; white-space: nowrap;">✏️ Edit</a>
                        <?php if ($u['Role'] !== 'admin'): ?>
                            <form method="POST" style="margin: 0;" onsubmit="return confirm('⚠️ PERMANENT DELETE: This will remove user <?= htmlspecialchars($u['Username']) ?> and ALL their race history (<?= $u['race_count'] ?> registration(s)). This action CANNOT be undone. Are you absolutely sure?');">
                                <input type="hidden" name="uid" value="<?= $u['UserID'] ?>">
                                <button type="submit" name="delete_participant" class="btn" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 8px 12px; white-space: nowrap;">🗑️ Delete</button>
                            </form>
                        <?php else: ?>
                            <button class="btn" disabled style="opacity: 0.4; padding: 8px 12px; white-space: nowrap; cursor: not-allowed;" title="Cannot delete admin users">🔒 Protected</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="card">
        <h3>4. Export All Tables (Read-Only)</h3>
        <p class="muted">Quick data review available. Use Edit/Delete buttons above for modifications.</p>
        <div class="table-scroll">
            <h4>Participant (Summary)</h4>
            <table class="data-table compact">
                <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Email</th><th>Phone</th></tr>
                <?php foreach($pdo->query("SELECT UserID, Username, Name, Role, Email, Phone FROM Participant ORDER BY UserID DESC") as $u): ?>
                    <tr>
                        <td><?= $u['UserID'] ?></td>
                        <td><?= $u['Username'] ?></td>
                        <td><?= $u['Name'] ?></td>
                        <td><?= $u['Role'] ?></td>
                        <td><?= $u['Email'] ?></td>
                        <td><?= $u['Phone'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h4>Marathon</h4>
            <table class="data-table compact">
                <tr><th>ID</th><th>Name</th><th>Date</th><th>Status</th><th>Cancel Reason</th></tr>
                <?php foreach($pdo->query("SELECT MarathonID, RaceName, Date, Status, CancelReason FROM Marathon ORDER BY Date DESC") as $m): ?>
                    <tr>
                        <td><?= $m['MarathonID'] ?></td>
                        <td><?= $m['RaceName'] ?></td>
                        <td><?= $m['Date'] ?></td>
                        <td><?= $m['Status'] ?></td>
                        <td><?= htmlspecialchars($m['CancelReason'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <h4>Participate</h4>
            <table class="data-table compact">
                <tr><th>MarathonID</th><th>UserID</th><th>Entry</th><th>Time</th><th>Rank</th></tr>
                <?php foreach($pdo->query("SELECT MarathonID, UserID, EntryNO, TimeRecord, Standings FROM Participate ORDER BY MarathonID DESC") as $p): ?>
                    <tr>
                        <td><?= $p['MarathonID'] ?></td>
                        <td><?= $p['UserID'] ?></td>
                        <td><?= $p['EntryNO'] ?></td>
                        <td><?= $p['TimeRecord'] ?></td>
                        <td><?= $p['Standings'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>