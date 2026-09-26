<?php
require_once 'db.php';

class DeliveryOrder {
    public static function create(string $name, string $address, string $phone, string $date, string $status): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO orders (customer_name, address, phone, delivery_date, status) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$name, $address, $phone, $date, $status]);
    }

    public static function getAll(string $search = '', string $status = ''): array {
        $db = Database::getConnection();
        $sql = "SELECT * FROM orders WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (customer_name LIKE ? OR phone LIKE ? OR address LIKE ?)";
            $keyword = "%{$search}%";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        return $order ?: null;
    }

    public static function update(int $id, string $name, string $address, string $phone, string $date, string $status): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE orders SET customer_name = ?, address = ?, phone = ?, delivery_date = ?, status = ? WHERE id = ?");
        return $stmt->execute([$name, $address, $phone, $date, $status, $id]);
    }

    public static function updateStatus(int $id, string $status): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        return $stmt->execute([$id]);
    }
}