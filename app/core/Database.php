<?php

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $host = DB_HOST;
        $dbname = DB_NAME;
        $username = DB_USER;
        $password = DB_PASS;

        try {

            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password
            );

            $this->connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $this->connection->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

        } catch (PDOException $exception) {

            die("Database Connection Failed : " . $exception->getMessage());

        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {

            self::$instance = new Database();

        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}