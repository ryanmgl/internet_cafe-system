<?php
$conn = new mysqli("localhost", "root", "", "computershop");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set("Asia/Manila");
$today = date("Y-m-d");

$sql = "
    SELECT s.*, u.type 
    FROM sessions s
    LEFT JOIN users u ON s.user_name = u.username
    WHERE DATE(s.start_time) = '$today' AND s.status = 'ended'
";

$result = $conn->query($sql);

$totalRevenue = 0;
$lines = [];
$lines[] = "DAILY REPORT for $today";
$lines[] = "===========================";

while ($row = $result->fetch_assoc()) {
    $start = strtotime($row['start_time']);
    $end = $row['end_time'] ? strtotime($row['end_time']) : time(); // fallback to now if still running
    $usedMinutes = ceil(($end - $start) / 60); //round up to minute

    $rate = ($row['type'] === 'vip') ? 0.40 : 0.50; //hardcoded prices
    $cost = $usedMinutes * $rate;

    $totalRevenue += $cost;

    $lines[] = "User: {$row['user_name']} | PC: {$row['pc_name']} | Type: {$row['type']} | Used: {$usedMinutes} mins | ₱" . number_format($cost, 2);
}


$lines[] = "===========================";
$lines[] = "Total Revenue: ₱" . number_format($totalRevenue, 2);

$report = implode("\n", $lines);

$filename = "daily_report_" . $today . ".txt";
file_put_contents($filename, $report);

header("Content-Type: text/plain");
header("Content-Disposition: attachment; filename=\"$filename\"");
readfile($filename);
unlink($filename);
exit;
