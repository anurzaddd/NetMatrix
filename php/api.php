<?php
// NetMatrix API
// Author: Amir Hossein Nourzadeh

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

$conn = getDBConnection();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_devices':
        $result = $conn->query("SELECT * FROM devices ORDER BY id DESC LIMIT 50");
        $devices = [];
        while ($row = $result->fetch_assoc()) {
            $devices[] = $row;
        }
        echo json_encode($devices);
        break;

    case 'get_device':
        $id = intval($_GET['id'] ?? 0);
        $result = $conn->query("SELECT * FROM devices WHERE id = $id");
        echo json_encode($result->fetch_assoc() ?? ['error' => 'Device not found']);
        break;

    case 'add_device':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("INSERT INTO devices (name, ip, mac, vendor, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $data['name'], $data['ip'], $data['mac'], $data['vendor'], $data['status']);
        echo json_encode(['success' => $stmt->execute()]);
        $stmt->close();
        break;

    case 'get_alerts':
        $result = $conn->query("SELECT * FROM alerts WHERE status='active' ORDER BY created_at DESC LIMIT 20");
        $alerts = [];
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }
        echo json_encode($alerts);
        break;

    case 'get_stats':
        $stats = [
            'devices' => $conn->query("SELECT COUNT(*) as count FROM devices")->fetch_assoc()['count'],
            'alerts' => $conn->query("SELECT COUNT(*) as count FROM alerts WHERE status='active'")->fetch_assoc()['count'],
            'users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count']
        ];
        echo json_encode($stats);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
