<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}
?>

<?php
$conn = new mysqli("localhost", "root", "", "computershop");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_user'])) {
    $newUser = trim($_POST['new_user']);
    if ($newUser != "") {
        $userType = $_POST['user_type'];
        $stmt = $conn->prepare("INSERT IGNORE INTO users (username, type) VALUES (?, ?)");
        $stmt->bind_param("ss", $newUser, $userType);
        $stmt->execute();
        echo "<p>User '$newUser' registered successfully.</p>";
    }
}

if (isset($_GET['end'])) {
    $id = intval($_GET['end']);
    $stmt = $conn->prepare("UPDATE sessions SET status = 'ended', end_time = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>Session ID $id has been ended successfully.</p>";
    } else {
        echo "<p style='color: red;'>Failed to end session ID $id.</p>";
    }
}

if (isset($_GET['delete_user'])) {
    $usernameToDelete = $_GET['delete_user'];
    $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
    $stmt->bind_param("s", $usernameToDelete);
    if ($stmt->execute()) {
        echo "<p style='color:red;'>User '" . htmlspecialchars($usernameToDelete) . "' has been removed.</p>";
    } else {
        echo "<p style='color:red;'>Failed to remove user '" . htmlspecialchars($usernameToDelete) . "'.</p>";
    }
}

?>
<p><a href="logout_admin.php" class="logout-link">Logout</a></p>
<link rel="stylesheet" href="style_admin.css">

<h2>Register New User</h2>
<form method="post">
    <input type="text" name="new_user" placeholder="Enter username" required>
    <select name="user_type" required>
        <option value="basic">Basic</option>
        <option value="vip">VIP</option>
    </select>
    <input type="submit" value="Register User">
</form>

<hr>
<h2>Admin Dashboard</h2>
<table border="1" cellpadding="10">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Type</th>
            <th>PC</th>
            <th>Plan</th>
            <th>Start Time</th>
            <th>Time Left</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody id="sessionTable">
        <!--load ajax here-->
    </tbody>
</table>

<script>
function loadSessions() {
    fetch('fetch_sessions.php')
        .then(res => res.text())
        .then(data => {
            document.getElementById('sessionTable').innerHTML = data;
        })
        .catch(err => {
            console.error('Fetch error:', err);
            document.getElementById('sessionTable').innerHTML = '<tr><td colspan="7">Failed to load session data.</td></tr>';
        });
}

loadSessions(); 
setInterval(loadSessions, 5000); // refresh for 5 secs
</script>

<hr>
<h2>Registered Users</h2>
<table border="1" cellpadding="10">
    <thead>
        <tr>
            <th>#</th>
            <th>Username</th>
            <th>Type</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $userQuery = $conn->query("SELECT * FROM users ORDER BY username ASC");
        $count = 1;
        while ($user = $userQuery->fetch_assoc()):
        ?>
        <tr>
            <td><?= $count++ ?></td>
            <td><?= htmlspecialchars($user['username']) ?></td>
            <td><?= htmlspecialchars($user['type']) ?></td>
            <td>
                <a href="?delete_user=<?= urlencode($user['username']) ?>" 
                onclick="return confirm('Are you sure you want to remove this user?');"
                style="color: red;">Remove</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<form method="post" action="generate_report.php" style="margin-bottom: 20px;">
    <button type="submit">Generate Daily Report</button>
</form>


