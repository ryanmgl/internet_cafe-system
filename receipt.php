<?php
$conn = new mysqli("localhost", "root", "", "computershop");

if (!isset($_GET['id'])) {
    echo "<p>Invalid session.</p>";
    exit;
}

$sessionId = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->bind_param("i", $sessionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $start = new DateTime($row['start_time']);
    $now = new DateTime();
    $elapsed = floor(($now->getTimestamp() - $start->getTimestamp()) / 60);
    if ($elapsed > $row['time_plan']) $elapsed = $row['time_plan'];

    $cost = $elapsed * 0.5;
    echo "
    <h2>Session Ended</h2>
    <p>User: <strong>{$row['user_name']}</strong> | PC: {$row['pc_name']}</p>
    <p>Time Used: {$elapsed} minutes</p>
    <p><strong>User Type:</strong> " . ucfirst($userType) . "</p>
    <p><strong>Rate:</strong> ₱" . number_format($rate, 2) . " / min</p>
    <p><strong>Total:</strong> ₱" . number_format($cost, 2) . "</p>
    <p><strong>Session Ended:</strong> " . ($row['end_time'] ?? $now->format("Y-m-d H:i:s")) . "</p>
    <a href='user.php'><button>Start New Session</button></a>
";

} else {
    echo "<p>Session not found.</p>";
}
?>
