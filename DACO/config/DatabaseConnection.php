<?php


class DatabaseConnection
{
    private mysqli $conn;

    public function __construct()
    {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if ($this->conn->connect_error) {
            die("<h2 style='color:red;font-family:sans-serif;padding:20px;'>
                 Database Connection Failed: "
                 . htmlspecialchars($this->conn->connect_error)
                 . "</h2>");
        }

        $this->conn->set_charset('utf8mb4');
    }

    public function getConnection(): mysqli
    {
        return $this->conn;
    }

    public function close(): void
    {
        $this->conn->close();
    }
}