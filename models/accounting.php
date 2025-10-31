<?php
// Stock Accounting model for tracking inflow/outflow entries

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/helpers.php';

class Accounting {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create stock_accounting table if not exists
     */
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS stock_accounting (
            id SERIAL PRIMARY KEY,
            record_id INTEGER REFERENCES records(id) ON DELETE CASCADE,
            entry_date DATE NOT NULL,
            quantity_in DECIMAL(10, 2) DEFAULT 0,
            quantity_out DECIMAL(10, 2) DEFAULT 0,
            balance DECIMAL(10, 2) NOT NULL,
            remarks TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        return pg_query($this->conn, $query);
    }
    
    /**
     * Get all accounting entries for a specific record
     */
    public function getByRecordId($recordId, $userId) {
        // Verify record belongs to user
        $verifyQuery = "SELECT id FROM records WHERE id = $1 AND user_id = $2";
        $verifyResult = pg_query_params($this->conn, $verifyQuery, [$recordId, $userId]);
        
        if (!$verifyResult || pg_num_rows($verifyResult) === 0) {
            return [];
        }
        
        $query = "SELECT * FROM stock_accounting 
                  WHERE record_id = $1 
                  ORDER BY entry_date DESC, created_at DESC";
        $result = pg_query_params($this->conn, $query, [$recordId]);
        
        if ($result) {
            return pg_fetch_all($result) ?: [];
        }
        
        return [];
    }
    
    /**
     * Get all accounting entries for all user's records
     */
    public function getAllByUserId($userId) {
        $query = "SELECT sa.*, r.code, r.color 
                  FROM stock_accounting sa
                  INNER JOIN records r ON sa.record_id = r.id
                  WHERE r.user_id = $1
                  ORDER BY sa.entry_date DESC, sa.created_at DESC";
        $result = pg_query_params($this->conn, $query, [$userId]);
        
        if ($result) {
            return pg_fetch_all($result) ?: [];
        }
        
        return [];
    }
    
    /**
     * Get single accounting entry by ID
     */
    public function getById($id, $userId) {
        $query = "SELECT sa.* 
                  FROM stock_accounting sa
                  INNER JOIN records r ON sa.record_id = r.id
                  WHERE sa.id = $1 AND r.user_id = $2";
        $result = pg_query_params($this->conn, $query, [$id, $userId]);
        
        if ($result && pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
        
        return null;
    }
    
    /**
     * Get latest balance for a record
     */
    public function getLatestBalance($recordId, $userId) {
        $query = "SELECT sa.balance 
                  FROM stock_accounting sa
                  INNER JOIN records r ON sa.record_id = r.id
                  WHERE sa.record_id = $1 AND r.user_id = $2
                  ORDER BY sa.entry_date DESC, sa.created_at DESC
                  LIMIT 1";
        $result = pg_query_params($this->conn, $query, [$recordId, $userId]);
        
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            return floatval($row['balance']);
        }
        
        return 0;
    }
    
    /**
     * Create new accounting entry
     */
    public function create($data) {
        $query = "INSERT INTO stock_accounting 
                  (record_id, entry_date, quantity_in, quantity_out, balance, remarks) 
                  VALUES ($1, $2, $3, $4, $5, $6) RETURNING id";
        
        $result = pg_query_params($this->conn, $query, [
            $data['record_id'],
            $data['entry_date'],
            $data['quantity_in'],
            $data['quantity_out'],
            $data['balance'],
            $data['remarks'] ?? null
        ]);
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            return ['success' => true, 'id' => $row['id']];
        }
        
        return ['success' => false, 'message' => 'Failed to create accounting entry'];
    }
    
    /**
     * Update accounting entry
     */
    public function update($id, $userId, $data) {
        // Verify entry belongs to user's record
        $verify = $this->getById($id, $userId);
        if (!$verify) {
            return ['success' => false, 'message' => 'Entry not found'];
        }
        
        $query = "UPDATE stock_accounting SET 
                  entry_date = $1,
                  quantity_in = $2,
                  quantity_out = $3,
                  balance = $4,
                  remarks = $5,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE id = $6";
        
        $result = pg_query_params($this->conn, $query, [
            $data['entry_date'],
            $data['quantity_in'],
            $data['quantity_out'],
            $data['balance'],
            $data['remarks'] ?? null,
            $id
        ]);
        
        if ($result && pg_affected_rows($result) > 0) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to update accounting entry'];
    }
    
    /**
     * Delete accounting entry
     */
    public function delete($id, $userId) {
        // Verify entry belongs to user's record
        $verify = $this->getById($id, $userId);
        if (!$verify) {
            return ['success' => false, 'message' => 'Entry not found'];
        }
        
        $query = "DELETE FROM stock_accounting WHERE id = $1";
        $result = pg_query_params($this->conn, $query, [$id]);
        
        if ($result && pg_affected_rows($result) > 0) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to delete accounting entry'];
    }
    
    /**
     * Get summary statistics for a record
     */
    public function getSummary($recordId, $userId) {
        $query = "SELECT 
                  COALESCE(SUM(quantity_in), 0) as total_in,
                  COALESCE(SUM(quantity_out), 0) as total_out,
                  (SELECT balance FROM stock_accounting 
                   WHERE record_id = $1 
                   ORDER BY entry_date DESC, created_at DESC LIMIT 1) as current_balance
                  FROM stock_accounting sa
                  INNER JOIN records r ON sa.record_id = r.id
                  WHERE sa.record_id = $1 AND r.user_id = $2";
        
        $result = pg_query_params($this->conn, $query, [$recordId, $userId]);
        
        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            return [
                'total_in' => floatval($row['total_in']),
                'total_out' => floatval($row['total_out']),
                'current_balance' => floatval($row['current_balance'] ?? 0)
            ];
        }
        
        return [
            'total_in' => 0,
            'total_out' => 0,
            'current_balance' => 0
        ];
    }
    
    /**
     * Count entries for a record
     */
    public function countByRecord($recordId, $userId) {
        $query = "SELECT COUNT(*) as total 
                  FROM stock_accounting sa
                  INNER JOIN records r ON sa.record_id = r.id
                  WHERE sa.record_id = $1 AND r.user_id = $2";
        $result = pg_query_params($this->conn, $query, [$recordId, $userId]);
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            return (int)$row['total'];
        }
        
        return 0;
    }
}
?>