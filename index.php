<?php
// index.php
session_start();
include 'lib/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hanoi Marathon System</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/gallery.js"></script>
</head>
<body>
    <div class="nav">
        <a href="index.php">Home</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="my_page.php">My Page</a>
            <?php if($_SESSION['role'] === 'admin'): ?>
                <a href="admin.php">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php">Logout (<?php echo $_SESSION['name']; ?>)</a>
        <?php else: ?>
            <a href="login.php" class="btn ghost">Login</a>
            <a href="register.php" class="btn primary">Register</a>
        <?php endif; ?>
    </div>

    <section class="hero">
        <div>
            <p class="eyebrow">Hanoi International Marathon</p>
            <h1>Manage registration, BIB numbers and results in one place.</h1>
            <p class="muted">Register, check participation history, and explore checkpoints with interactive gallery.</p>
            <div class="actions">
                <a class="btn primary" href="register.php">Join Now</a>
                <a class="btn ghost" href="#gallery">Explore Route</a>
            </div>
        </div>
        <div class="stats">
            <div class="stat-card"><span class="stat-number">42 km</span><span class="stat-label">Full Marathon</span></div>
            <div class="stat-card"><span class="stat-number">15 km</span><span class="stat-label">West Lake Checkpoint</span></div>
            <div class="stat-card"><span class="stat-number">4</span><span class="stat-label">Photo Points</span></div>
        </div>
    </section>

    <section id="gallery" class="card">
        <h3>Marathon Route Map — Browse All Races</h3>
        <p class="muted">Click on a race to view route photos and register. Registration closes on race day.</p>
        
        <div class="marathons-list">
            <?php
            $all_marathons = $pdo->query("SELECT * FROM Marathon ORDER BY Date ASC")->fetchAll();
            $user_registered = [];
            if (isset($_SESSION['user_id'])) {
                $stmt = $pdo->prepare("SELECT MarathonID FROM Participate WHERE UserID = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user_registered = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            
            foreach ($all_marathons as $idx => $race):
                $race_date = new DateTime($race['Date']);
                $today = new DateTime('today');
                $is_past = $race_date < $today;
                $is_cancelled = $race['Status'] !== 'Scheduled';
                $is_registered = in_array($race['MarathonID'], $user_registered);
                $can_register = !$is_past && !$is_cancelled && !$is_registered;
            ?>
            <div class="accordion-item">
                <div class="accordion-header">
                    <div class="accordion-title">
                        <span class="accordion-icon">▶</span>
                        <strong><?= htmlspecialchars($race['RaceName']) ?></strong>
                        <span class="race-date"><?= $race['Date'] ?></span>
                        <span class="tag <?= strtolower($race['Status']) ?>"><?= $race['Status'] ?></span>
                        <?php if ($is_registered): ?>
                            <span class="tag registered">✓ Registered</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="accordion-content" id="accordion-<?= $race['MarathonID'] ?>">
                    <div class="race-details">
                        <!-- Route Images Gallery -->
                        <div class="race-gallery">
                            <div class="route-images">
                                <img src="Image/p1.jpg" alt="Route Map" class="route-img img-large" title="Route Map">
                                <img src="Image/p2.jpg" alt="Checkpoint 1" class="route-img img-medium" title="Checkpoint 1: West Lake">
                                <img src="Image/p3.jpg" alt="Event Scene" class="route-img img-medium" title="Event Scene">
                                <img src="Image/p4.jpg" alt="Finish Line" class="route-img img-medium" title="Finish Line">
                                <img src="Image/p1.jpg" alt="Participants" class="route-img img-medium" title="Participants">
                                <img src="Image/p4.jpg" alt="Venue" class="route-img img-large" title="Venue">
                            </div>

                            <!-- Registration Button -->
                            <div class="registration-section">
                                <?php if ($can_register && isset($_SESSION['user_id'])): ?>
                                    <form method="POST" action="my_page.php">
                                        <input type="hidden" name="mid" value="<?= $race['MarathonID'] ?>">
                                        <button type="submit" name="join_marathon" class="btn primary">Register</button>
                                    </form>
                                <?php elseif ($is_registered): ?>
                                    <button class="btn primary" disabled style="opacity: 0.6;">✓ Registered</button>
                                <?php elseif ($is_past): ?>
                                    <button class="btn ghost" disabled style="opacity: 0.5;">Registration closed</button>
                                <?php elseif ($is_cancelled): ?>
                                    <button class="btn ghost" disabled style="opacity: 0.5;">Race Cancelled</button>
                                <?php else: ?>
                                    <a href="login.php" class="btn primary">Login to Register</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Race Info (Right Column) -->
                        <div class="race-info">
                            <div class="info-row">
                                <span class="label">Date:</span>
                                <span><?= $race['Date'] ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Status:</span>
                                <span class="tag <?= strtolower($race['Status']) ?>"><?= $race['Status'] ?></span>
                            </div>
                            <?php if (isset($race['CancelReason']) && $race['CancelReason']): ?>
                            <div class="info-row" style="color: #fecaca;">
                                <span class="label">Reason:</span>
                                <span><?= htmlspecialchars($race['CancelReason']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-box" style="background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.3); padding: 12px; border-radius: 8px; margin-top: 8px;">
                                <p style="margin: 0; font-size: 13px; color: var(--muted);">
                                    <strong style="color: var(--accent);">Full Marathon</strong><br>
                                    Distance: 42 km<br>
                                    Route: Hanoi City Center
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
</body>
</html>