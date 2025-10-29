<?php
// User model for authentication

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/helpers.php';

class User {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create users table if not exists
     */
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        return pg_query($this->conn, $query);
    }
    
    /**
     * Register new user
     */
    public function register($name, $email, $password) {
        // Check if email already exists
        if ($this->findByEmail($email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $query = "INSERT INTO users (name, email, password) VALUES ($1, $2, $3) RETURNING id";
        $result = pg_query_params($this->conn, $query, [$name, $email, $hashedPassword]);
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            return ['success' => true, 'user_id' => $row['id']];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        if (password_verify($password, $user['password'])) {
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ];
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM users WHERE email = $1";
        $result = pg_query_params($this->conn, $query, [$email]);
        
        if ($result && pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
        
        return null;
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        $query = "SELECT * FROM users WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$id]);
        
        if ($result && pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
        
        return null;
    }
}
?>