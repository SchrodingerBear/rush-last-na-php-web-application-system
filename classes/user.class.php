<?php
require_once 'connection.php';

class Academai {
    //attributes
    public $creation_id;
    public $first_name;
    public $middle_name;
    public $last_name;
    public $email;
    public $password;
    public $is_verified;
    public $verification_code;
    public $verification_expiry;

    protected $db;

    function __construct() {
        $this->db = new Database();
    }

    //Methods

    function add() {
        // Generate verification code and expiry
        $this->verification_code = md5(uniqid(rand(), true));
        $this->verification_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $this->is_verified = 0; // Set to false by default

        $sql = "INSERT INTO academai (first_name, middle_name, last_name, email, password, is_verified, verification_code, verification_expiry) 
                VALUES (:first_name, :middle_name, :last_name, :email, :password, :is_verified, :verification_code, :verification_expiry);";

        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':first_name', $this->first_name);
        $query->bindParam(':middle_name', $this->middle_name);
        $query->bindParam(':last_name', $this->last_name);
        $query->bindParam(':email', $this->email);
        
        // Hash the password securely using password_hash
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        $query->bindParam(':password', $hashedPassword);
        
        $query->bindParam(':is_verified', $this->is_verified, PDO::PARAM_BOOL);
        $query->bindParam(':verification_code', $this->verification_code);
        $query->bindParam(':verification_expiry', $this->verification_expiry);
        
        if ($query->execute()) {
            return $this->verification_code; // Return the verification code for email sending
        } else {
            return false;
        }    
    }

    function verifyEmail($code) {
        // Check if code exists and is not expired
        $sql = "SELECT creation_id FROM academai WHERE verification_code = :code AND verification_expiry > NOW()";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':code', $code);
        $query->execute();
        
        if ($query->rowCount() > 0) {
            $userId = $query->fetchColumn();
            
            // Mark as verified and clear verification fields
            $updateSql = "UPDATE academai SET is_verified = 1, verification_code = NULL, verification_expiry = NULL 
                         WHERE creation_id = :id";
            $updateQuery = $this->db->connect()->prepare($updateSql);
            $updateQuery->bindParam(':id', $userId);
            return $updateQuery->execute();
        }
        
        return false;
    }

    function login($email, $password) {
        $sql = "SELECT * FROM academai WHERE email = :email";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':email', $email);
        $query->execute();
        
        $user = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_verified']) {
                throw new Exception("Please verify your email address first. Check your inbox for the verification link.");
            }
            return $user;
        }
        return false;
    }

    function fetch($creation_id) {
        $sql = "SELECT * FROM academai WHERE creation_id = :creation_id;";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':creation_id', $creation_id);
        if ($query->execute()) {
            $data = $query->fetch();
        }
        return $data;
    }

    function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM academai WHERE email = :email";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':email', $email);
        $query->execute();
        return $query->fetchColumn() > 0;
    }

    function resendVerification($email) {
        $sql = "SELECT creation_id, verification_code FROM academai WHERE email = :email AND is_verified = 0";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':email', $email);
        $query->execute();
        
        if ($query->rowCount() > 0) {
            $user = $query->fetch(PDO::FETCH_ASSOC);
            
            // Update verification code and expiry
            $newCode = md5(uniqid(rand(), true));
            $newExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $updateSql = "UPDATE academai SET verification_code = :code, verification_expiry = :expiry 
                         WHERE creation_id = :id";
            $updateQuery = $this->db->connect()->prepare($updateSql);
            $updateQuery->bindParam(':code', $newCode);
            $updateQuery->bindParam(':expiry', $newExpiry);
            $updateQuery->bindParam(':id', $user['creation_id']);
            
            if ($updateQuery->execute()) {
                return $newCode; // Return the new code for email sending
            }
        }
        
        return false;
    }

    // Get user by verification code
    function getUserByVerificationCode($code) {
        $sql = "SELECT * FROM academai WHERE verification_code = :code";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':code', $code);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    // Verify user by ID
    function verifyUser($creation_id) {
        $sql = "UPDATE academai SET is_verified = 1, verification_code = NULL, verification_expiry = NULL 
                WHERE creation_id = :id";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':id', $creation_id);
        return $query->execute();
    }
}
?>