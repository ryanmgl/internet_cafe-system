<?php
$conn = new mysqli("localhost", "root", "", "computershop");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = $conn->query("
    SELECT s.*, u.type 
    FROM sessions s
    LEFT JOIN users u ON s.user_name = u.username
    WHERE s.status = 'in_use'
");

while ($row = $query->fetch_assoc()) {
    $start = new DateTime($row['start_time']);
    $now = new DateTime();
    $end = clone $start;
    $end->modify("+" . (int)$row['time_plan'] . " minutes");

    $remainingSeconds = $end->getTimestamp() - $now->getTimestamp();

    if ($remainingSeconds > 0) {
        $minutes = floor($remainingSeconds / 60);
        $seconds = $remainingSeconds % 60;
        $timeLeft = "{$minutes}m " . str_pad($seconds, 2, "0", STR_PAD_LEFT) . "s left";
    } else {
        $timeLeft = "0m 00s (expired)";
    }

    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['user_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['type'] ?? 'basic') . "</td>";
    echo "<td>" . htmlspecialchars($row['pc_name']) . "</td>";
    echo "<td>" . $row['time_plan'] . " mins</td>";
    echo "<td>" . $row['start_time'] . "</td>";
    echo "<td>$timeLeft</td>";
    echo "<td><a href='admin.php?end=" . $row['id'] . "' onclick='return confirm(\"End this session?\");'>End</a></td>";
    echo "</tr>";
}
?>
