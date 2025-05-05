<?php

class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'academaidb';
    protected $connection;

    public function connect() {
        try {
            $this->connection = new PDO("mysql:host=$this->host;dbname=$this->database", 
                                        $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Log error to a file
            error_log("Database connection failed: " . $e->getMessage(), 3, 'errors.log');
            // Show a generic message to the user
            die("A database connection error occurred. Please try again later.");
        }
        return $this->connection;
    }
    
}

// Do not instantiate or call methods here
?>
