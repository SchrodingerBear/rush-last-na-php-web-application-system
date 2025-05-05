<?php
require_once 'connection.php';

class Account {
    public $creation_id;
    public $email;
    public $password;
    public $photo_path; // Add photo_path property
    public $first_name;
    public $middle_name;
    public $last_name;

    protected $db;

    function __construct() {
        $this->db = new Database();
    }

    function sign_in_customer() {
        $sql = "SELECT creation_id, email, password, photo_path, first_name, middle_name, last_name FROM academai WHERE email = :email LIMIT 1";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':email', $this->email);

        if ($query->execute()) {
            $accountData = $query->fetch(PDO::FETCH_ASSOC);

            if ($accountData && password_verify($this->password, $accountData['password'])) {
                $this->creation_id = $accountData['creation_id'];
                $this->photo_path = $accountData['photo_path'] ?? null; // Store photo path
                $this->first_name = $accountData['first_name'] ?? null;
                $this->middle_name = $accountData['middle_name'] ?? null;
                $this->last_name = $accountData['last_name'] ?? null;
                return true;
            }
        }
        return false;
    }

    public function getUserDetails() {
        if (empty($this->creation_id)) {
            error_log("Creation ID is not set.");
            return false;
        }

        $sql = "SELECT first_name, middle_name, last_name, photo_path FROM academai WHERE creation_id = :creation_id";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':creation_id', $this->creation_id, PDO::PARAM_INT);

        try {
            $query->execute();
            $result = $query->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $this->first_name = $result['first_name'] ?? null;
                $this->middle_name = $result['middle_name'] ?? null;
                $this->last_name = $result['last_name'] ?? null;
                $this->photo_path = $result['photo_path'] ?? null;
            }

            error_log("Query result: " . print_r($result, true));
            return $result;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }

    public function getUserCreationId() {
        return $this->creation_id;
    }

    public function getPhotoPath() {
        $default_avatar = '../img/default-avatar.jpg';
        
        if (empty($this->photo_path)) {
            return $default_avatar;
        }

        // Try different possible paths
        $possiblePaths = [
            '../uploads/profile/' . basename($this->photo_path),
            '../' . $this->photo_path,
            'uploads/profile/' . basename($this->photo_path),
            $this->photo_path
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return $default_avatar;
    }
}
?>