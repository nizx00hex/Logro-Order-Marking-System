<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'DeliveryOrder.php';

$action = $_GET['action'] ?? '';
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true) ?? [];

switch ($action) {
    case 'read':
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        echo json_encode(["status" => "success", "data" => DeliveryOrder::getAll($search, $status)]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $order = DeliveryOrder::getById($id);
        echo json_encode($order ? ["status" => "success", "data" => $order] : ["status" => "error", "message" => "Not found"]);
        break;

    case 'create':
        if (empty($data['customer_name']) || empty($data['address']) || empty($data['phone']) || empty($data['delivery_date'])) {
            echo json_encode(["status" => "error", "message" => "All fields required"]);
            exit;
        }
        $status = $data['status'] === 'delivered' ? 'delivered' : 'pending';
        $res = DeliveryOrder::create(trim($data['customer_name']), trim($data['address']), trim($data['phone']), trim($data['delivery_date']), $status);
        echo json_encode(["status" => $res ? "success" : "error"]);
        break;

    case 'update':
        $id = (int)($data['id'] ?? 0);
        $status = $data['status'] === 'delivered' ? 'delivered' : 'pending';
        $res = DeliveryOrder::update($id, trim($data['customer_name']), trim($data['address']), trim($data['phone']), trim($data['delivery_date']), $status);
        echo json_encode(["status" => $res ? "success" : "error"]);
        break;

    case 'update_status':
        $id = (int)($data['id'] ?? 0);
        $status = $data['status'] === 'delivered' ? 'delivered' : 'pending';
        $res = DeliveryOrder::updateStatus($id, $status);
        echo json_encode(["status" => $res ? "success" : "error"]);
        break;

    case 'delete':
        $id = (int)($data['id'] ?? 0);
        $res = DeliveryOrder::delete($id);
        echo json_encode(["status" => $res ? "success" : "error"]);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid endpoint"]);
        break;
}