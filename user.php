<?php
$conn = new mysqli("localhost", "root", "", "computershop");
$showTimer = false;
$planMinutes = 0;
$sessionId = null;
$receipt = "";
$sessionEnded = false;

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// end session
if (isset($_GET['end'])) {
    $sessionId = $_GET['end'];
    $stmt = $conn->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->bind_param("i", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // check if already ended
    if ($row['status'] === 'in_use') {
        $endTime = date("Y-m-d H:i:s");
        $updateStmt = $conn->prepare("UPDATE sessions SET status = 'ended', end_time = ? WHERE id = ?");
        $updateStmt->bind_param("si", $endTime, $sessionId);
        $updateStmt->execute();

    }

    $start = new DateTime($row['start_time']);
    $now = new DateTime();
    $end = new DateTime();
    if ($row['end_time']) {
        $end = new DateTime($row['end_time']);
    }
    $elapsed = floor(($end->getTimestamp() - $start->getTimestamp()) / 60);
    if ($elapsed > $row['time_plan']) $elapsed = $row['time_plan'];


    // customer type
    $getType = $conn->prepare("SELECT type FROM users WHERE username = ?");
    $getType->bind_param("s", $row['user_name']);
    $getType->execute();
    $typeResult = $getType->get_result();
    $typeRow = $typeResult->fetch_assoc();
    $userType = $typeRow ? $typeRow['type'] : 'basic';

    $rate = ($userType === 'vip') ? 0.4 : 0.5;
    $cost = $elapsed * $rate;

    $receipt = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Session Receipt</title>
        <link rel="stylesheet" href="style_user.css">
    </head>
    <body class="receipt-page">
        <div class="receipt-container">
            <h2>🧾 Session Receipt</h2>
            <div class="receipt-details">
                <p><strong>User:</strong> ' . htmlspecialchars($row['user_name']) . '</p>
                <p><strong>PC:</strong> ' . htmlspecialchars($row['pc_name']) . '</p>
                <p><strong>Time Used:</strong> ' . $elapsed . ' minutes</p>
                <p class="total"><strong>Total:</strong> ₱' . number_format($cost, 2) . '</p>
            </div>
            <a href="user.php" class="receipt-button">Start New Session</a>
        </div>
    </body>
    </html>';


    $sessionEnded = true;
  }
}

// start session
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);

        // Ccheck if registered
        $checkUser = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $checkUser->bind_param("s", $user);
        $checkUser->execute();
        $userResult = $checkUser->get_result();

        if ($userResult->num_rows === 0) {
            echo "<p style='color:red;'>User '$user' is not registered. Please contact admin.</p>";
            exit;
        }

        $pc = $_POST['pc'];
        $plan = $_POST['plan'];

        //check if pc is already in use
        $checkPC = $conn->prepare("SELECT * FROM sessions WHERE pc_name = ? AND status = 'in_use'");
        $checkPC->bind_param("s", $pc);
        $checkPC->execute();
        $resultPC = $checkPC->get_result();

        if ($resultPC->num_rows > 0) {
            echo "<p style='color:red;'>PC '$pc' is currently in use. Please choose another.</p>";
            exit;
        }

        $start = date("Y-m-d H:i:s");
        $status = "in_use";

        $stmt = $conn->prepare("INSERT INTO sessions (user_name, pc_name, time_plan, start_time, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $user, $pc, $plan, $start, $status);

        if ($stmt->execute()) {
            $planMinutes = $plan;
            $sessionId = $stmt->insert_id;
            $showTimer = true;

            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Session Timer</title>
                <link rel="stylesheet" href="style_user.css">
            </head>
            <body>
                <div class="session-started-box">
                    <h2>Session Started</h2>
                    <p><strong>PC:</strong> <?= htmlspecialchars($pc) ?> | <strong>Plan:</strong> <?= $plan ?> minutes | <strong>Welcome,</strong> <?= htmlspecialchars($user) ?>!</p>
                    <div id="timer"></div>
                    <a href="user.php?end=<?= $sessionId ?>" class="end-button">End Session Now</a>
                </div>


                <script>
                    function startTimer(durationMinutes, sessionId) {
                        let time = durationMinutes * 60;
                        const timerDisplay = document.getElementById("timer");

                        const countdown = setInterval(() => {
                            let minutes = Math.floor(time / 60);
                            let seconds = time % 60;

                            timerDisplay.innerHTML = `<h3>Time Left: ${minutes}m ${seconds < 10 ? "0" : ""}${seconds}s</h3>`;

                            if (time <= 0) {
                                clearInterval(countdown);
                                timerDisplay.innerHTML = "<h3 style='color:red;'>Session Ended Automatically</h3>";
                                setTimeout(() => {
                                    window.location.href = "user.php?end=" + sessionId;
                                }, 4000);
                            }

                            time--;
                        }, 1000);

                        const checkSession = setInterval(() => {
                            fetch(`check_session.php?id=${sessionId}`)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.status === "ended") {
                                        clearInterval(countdown);
                                        clearInterval(checkSession);
                                        fetch("user.php?end=" + sessionId)
                                            .then(response => response.text())
                                            .then(html => {
                                                document.body.innerHTML = html;
                                            });
                                    }
                                });
                        }, 5000);
                    }

                    startTimer(<?= $planMinutes ?>, <?= $sessionId ?>);
</script>

            </body>
            </html>
            <?php
            exit; // prevent any HTML from loading
        } else {
            echo "<p style='color:red;'>Failed to insert session: " . $stmt->error . "</p>";
}

}
?>

<?php if ($_SERVER["REQUEST_METHOD"] != "POST" && !$sessionEnded): ?>
<link rel="stylesheet" href="style_user.css">
<h2>Start a Session</h2>
<form method="post">
    Username: <input type="text" name="username" required><br><br>
    Select PC:
    <select name="pc">
        <option value="PC1">PC1</option>
        <option value="PC2">PC2</option>
        <option value="PC3">PC3</option>
        <option value="PC4">PC4</option>
        <option value="PC5">PC5</option>

    </select><br><br>
    Choose Time Plan:
    <select name="plan">
        <option value="30">30 mins</option>
        <option value="60">1 hour</option>
    </select><br><br>
    <input type="submit" value="Start Session">
</form>
<?php endif; ?>

<?php
if ($sessionEnded) {
    echo "<hr>$receipt";
    exit; // prevent session form or script errors
}

?>

<script>
function startTimer(durationMinutes, sessionId) {
    let time = durationMinutes * 60;
    const timerDisplay = document.getElementById("timer");

    const countdown = setInterval(() => {
        let minutes = Math.floor(time / 60);
        let seconds = time % 60;

        timerDisplay.innerHTML = `<h3>Time Left: ${minutes}m ${seconds < 10 ? "0" : ""}${seconds}s</h3>`;

        if (time <= 0) {
            clearInterval(countdown);
            timerDisplay.innerHTML = "<h3 style='color:red;'>Session Ended Automatically</h3>";
            setTimeout(() => {
                window.location.href = "user.php?end=" + sessionId;
            }, 4000);
        }

        time--;
    }, 1000);

    // checks admin every 5s
    const checkSession = setInterval(() => {
        fetch(`check_session.php?id=${sessionId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === "ended") {
                    clearInterval(countdown);
                    clearInterval(checkSession);
                    fetch("user.php?end=" + sessionId)
                    .then(response => response.text())
                    .then(html => {
        document.body.innerHTML = html;
    });
                }
            });
    }, 5000);
}

<?php if ($showTimer && $sessionId): ?>
    startTimer(<?= $planMinutes ?>, <?= $sessionId ?>);
<?php endif; ?>
</script>
