<?php
class Database {
    private static ?PDO $connection = null;

    public static function getConnection(): PDO {
        if (self::$connection === null) {
            // Check for user-data path passed from Electron, otherwise fall back to local dir
            $dataDir = getenv('APP_DATA_DIR');
            if (!$dataDir || !is_dir($dataDir)) {
                $dataDir = __DIR__;
            }

            $dbPath = rtrim($dataDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'delivery_app.db';
            $isNew = !file_exists($dbPath);

            self::$connection = new PDO("sqlite:" . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            if ($isNew) {
                self::$connection->exec("
                    CREATE TABLE IF NOT EXISTS orders (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        customer_name TEXT NOT NULL,
                        address TEXT NOT NULL,
                        phone TEXT NOT NULL,
                        delivery_date TEXT NOT NULL,
                        status TEXT CHECK(status IN ('pending', 'delivered')) DEFAULT 'pending',
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                ");
            }
        }
        return self::$connection;
    }
}