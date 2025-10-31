<!-- /models/record.php -->
<?php
// Record model for stock book entries

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/helpers.php';

class Record {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create records table if not exists
     */
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS records (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            code VARCHAR(50) UNIQUE NOT NULL,
            color VARCHAR(50) NOT NULL,
            net_weight DECIMAL(10, 2) NOT NULL,
            gauge VARCHAR(10) NOT NULL,
            sales_status VARCHAR(50) NOT NULL,
            no_of_meters DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        return pg_query($this->conn, $query);
    }
    
    /**
     * Get all records for a user
     */
    public function getAll($userId, $limit = null, $offset = 0) {
        $query = "SELECT * FROM records WHERE user_id = $1 ORDER BY created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT $2 OFFSET $3";
            $result = pg_query_params($this->conn, $query, [$userId, $limit, $offset]);
        } else {
            $result = pg_query_params($this->conn, $query, [$userId]);
        }
        
        if ($result) {
            return pg_fetch_all($result) ?: [];
        }
        
        return [];
    }
    
    /**
     * Get single record by ID
     */
    public function getById($id, $userId) {
        $query = "SELECT * FROM records WHERE id = $1 AND user_id = $2";
        $result = pg_query_params($this->conn, $query, [$id, $userId]);
        
        if ($result && pg_num_rows($result) > 0) {
            return pg_fetch_assoc($result);
        }
        
        return null;
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        $query = "INSERT INTO records (user_id, code, color, net_weight, gauge, sales_status, no_of_meters) 
                  VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING id";
        
        $result = pg_query_params($this->conn, $query, [
            $data['user_id'],
            $data['code'],
            $data['color'],
            $data['net_weight'],
            $data['gauge'],
            $data['sales_status'],
            $data['no_of_meters']
        ]);
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            return ['success' => true, 'id' => $row['id']];
        }
        
        return ['success' => false, 'message' => 'Failed to create record'];
    }
    
    /**
     * Update record
     */
    public function update($id, $userId, $data) {
        $query = "UPDATE records SET 
                  color = $1, 
                  net_weight = $2, 
                  gauge = $3, 
                  sales_status = $4, 
                  no_of_meters = $5,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE id = $6 AND user_id = $7";
        
        $result = pg_query_params($this->conn, $query, [
            $data['color'],
            $data['net_weight'],
            $data['gauge'],
            $data['sales_status'],
            $data['no_of_meters'],
            $id,
            $userId
        ]);
        
        if ($result && pg_affected_rows($result) > 0) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to update record'];
    }
    
    /**
     * Delete record
     */
    public function delete($id, $userId) {
        $query = "DELETE FROM records WHERE id = $1 AND user_id = $2";
        $result = pg_query_params($this->conn, $query, [$id, $userId]);
        
        if ($result && pg_affected_rows($result) > 0) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to delete record'];
    }
    
    /**
     * Count total records for a user
     */
    public function count($userId) {
        $query = "SELECT COUNT(*) as total FROM records WHERE user_id = $1";
        $result = pg_query_params($this->conn, $query, [$userId]);
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            return (int)$row['total'];
        }
        
        return 0;
    }
    
    /**
     * Check if code exists
     */
    public function codeExists($code, $userId, $excludeId = null) {
        if ($excludeId) {
            $query = "SELECT id FROM records WHERE code = $1 AND user_id = $2 AND id != $3";
            $result = pg_query_params($this->conn, $query, [$code, $userId, $excludeId]);
        } else {
            $query = "SELECT id FROM records WHERE code = $1 AND user_id = $2";
            $result = pg_query_params($this->conn, $query, [$code, $userId]);
        }
        
        return $result && pg_num_rows($result) > 0;
    }
    /**
     * ✅ NEW: Get accounting summary for a record
     */
    public function getAccountingSummary($recordId, $userId) {
        // Verify record belongs to user
        $record = $this->getById($recordId, $userId);
        if (!$record) {
            return null;
        }
        
        $query = "SELECT 
                    COUNT(*) as total_entries,
                    COALESCE(SUM(quantity_in), 0) as total_in,
                    COALESCE(SUM(quantity_out), 0) as total_out,
                    COALESCE(MAX(balance), 0) as current_balance
                  FROM stock_accounting 
                  WHERE record_id = $1";
        
        $result = pg_query_params($this->conn, $query, [$recordId]);
        
        if ($result) {
            return pg_fetch_assoc($result);
        }
        
        return null;
    }
}
?>