<?php
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string|false {
        $stmt = $this->conn->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['data'];
        }
        return ""; // Return empty string if session doesn't exist yet
    }

    public function write($id, $data): bool {
        // REPLACE INTO automatically inserts a new row or updates an existing one if the ID matches
        $stmt = $this->conn->prepare("REPLACE INTO sessions (id, data, last_accessed) VALUES (?, ?, CURRENT_TIMESTAMP)");
        $stmt->bind_param("ss", $id, $data);
        return $stmt->execute();
    }

    public function destroy($id): bool {
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }

    public function gc($max_lifetime): int|false {
        // Garbage Collection: Delete sessions older than the max lifetime
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE last_accessed < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? SECOND)");
        $stmt->bind_param("i", $max_lifetime);
        $stmt->execute();
        return $stmt->affected_rows;
    }
}
?>