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
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            stock_type_id INT NOT NULL,
            code VARCHAR(50) NOT NULL,
            color VARCHAR(100),
            net_weight DECIMAL(12,2),
            gauge VARCHAR(20),
            sales_status ENUM('Factory Use', 'In Production', 'Sold Out') NOT NULL DEFAULT 'Factory Use',
            no_of_meters DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_code (user_id, code),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (stock_type_id) REFERENCES stock_types(id) ON DELETE RESTRICT,
            INDEX (stock_type_id),
            INDEX (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        return $this->conn->query($query);
    }
    
    /**
     * Get all records for a user, optionally filtered by stock type
     * 
     * @param int $userId The ID of the user
     * @param int|null $stockTypeId Optional stock type ID to filter by
     * @param int|null $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array Array of records
     */
    // models/record.php - Update the getAll method
public function getAll($userId, $stockTypeId = null, $limit = null, $offset = 0) {
    $query = "SELECT r.*, st.name as stock_type_name 
             FROM records r
             JOIN stock_types st ON r.stock_type_id = st.id
             WHERE r.user_id = ?";
    
    $types = "i";
    $params = [$userId];
    
    // Add stock type filter if provided
    if ($stockTypeId !== null) {
        $query .= " AND r.stock_type_id = ?";
        $types .= "i";
        $params[] = $stockTypeId;
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    // Add pagination if needed
    if ($limit !== null) {
        $query .= " LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;
    }
    
    $stmt = $this->conn->prepare($query);
    
    // Bind parameters dynamically
    $bindParams = [&$types];
    foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key];
    }
    
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC) ?: [];
    }
    
    return [];
}
    
    /**
     * Get single record by ID
     */
    public function getById($id, $userId) {
        error_log("Executing getById - ID: $id, UserID: $userId");
        $query = "SELECT * FROM records WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("ii", $id, $userId);
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return null;
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            error_log("Get result failed: " . $stmt->error);
            return null;
        }
        
        if ($result->num_rows > 0) {
            $record = $result->fetch_assoc();
            error_log("Record found: " . json_encode($record));
            return $record;
        }
        
        error_log("No record found for ID: $id and UserID: $userId");
        return null;
    }
    
    /**
     * Create new record
     */
   // In models/record.php
public function create($data) {
    // Use the stored procedure for creating records with opening balance
    $query = "CALL create_record_with_opening(?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $this->conn->prepare($query);
    
    // Set default values if not provided
    $userId = $data['user_id'];
    $stockTypeId = $data['stock_type_id'];
    $code = $data['code'];
    $color = $data['color'] ?? null;
    $netWeight = $data['net_weight'] ?? null;
    $gauge = $data['gauge'] ?? null;
    $noOfMeters = $data['no_of_meters'] ?? 0.00;
    $remarks = 'Initial stock entry';
    
    // Bind parameters
    $stmt->bind_param("iissddss", 
        $userId,
        $stockTypeId,
        $code,
        $color,
        $netWeight,
        $gauge,
        $noOfMeters,
        $remarks
    );
    
    if ($stmt->execute()) {
        // Store the result
        $stmt->store_result();
        
        // Get the output parameter (assuming your stored procedure sets an output parameter for the ID)
        $newId = 0;
        $stmt->bind_result($newId);
        $stmt->fetch();
        
        // Free the result
        $stmt->free_result();
        
        // Consume any remaining results from the stored procedure
        while ($this->conn->more_results()) {
            $this->conn->next_result();
            if ($result = $this->conn->store_result()) {
                $result->free();
            }
        }
        
        return ['success' => true, 'id' => $newId];
    }
    
    return ['success' => false, 'message' => 'Failed to create record: ' . $this->conn->error];
}
    
    /**
     * Update record
     */
    public function update($id, $userId, $data) {
        $query = "UPDATE records SET 
                  color = ?, 
                  net_weight = ?, 
                  gauge = ?, 
                  sales_status = ?, 
                  no_of_meters = ?,
                  updated_at = CURRENT_TIMESTAMP
                  WHERE id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sdssdii", 
            $data['color'],
            $data['net_weight'],
            $data['gauge'],
            $data['sales_status'],
            $data['no_of_meters'],
            $id,
            $userId
        );
        
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to update record: ' . $this->conn->error];
    }
    
    /**
     * Delete record
     */
    public function delete($id, $userId) {
    // Verify entry belongs to user's record and get record_id
    $entry = $this->getById($id, $userId);
    if (!$entry) {
        return ['success' => false, 'message' => 'Entry not found or access denied'];
    }
    
    $this->conn->begin_transaction();
    
    try {
        // Get the entry to be deleted
        $entryStmt = $this->conn->prepare("
            SELECT * FROM stock_accounting 
            WHERE id = ? AND record_id = ?
            FOR UPDATE
        ");
        $entryStmt->bind_param("ii", $id, $entry['record_id']);
        $entryStmt->execute();
        $entry = $entryStmt->get_result()->fetch_assoc();
        
        if (!$entry) {
            throw new Exception('Entry not found');
        }
        
        // Get the balance before this entry
        $prevBalance = 0;
        $prevStmt = $this->conn->prepare("
            SELECT balance 
            FROM stock_accounting 
            WHERE record_id = ? AND id < ? 
            ORDER BY entry_date DESC, id DESC 
            LIMIT 1
        ");
        $prevStmt->bind_param("ii", $entry['record_id'], $id);
        $prevStmt->execute();
        $prevResult = $prevStmt->get_result();
        if ($prevResult->num_rows > 0) {
            $prevBalance = $prevResult->fetch_assoc()['balance'];
        }
        
        // Get all entries after this one to recalculate balances
        $stmt = $this->conn->prepare("
            SELECT id, quantity_in, quantity_out 
            FROM stock_accounting 
            WHERE record_id = ? AND id > ? 
            ORDER BY entry_date, id
            FOR UPDATE
        ");
        $stmt->bind_param("ii", $entry['record_id'], $id);
        $stmt->execute();
        $subsequentEntries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Delete the entry
        $deleteStmt = $this->conn->prepare("DELETE FROM stock_accounting WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        
        if (!$deleteStmt->execute() || $deleteStmt->affected_rows === 0) {
            throw new Exception('Failed to delete accounting entry');
        }
        
        // Recalculate balances for subsequent entries
        $currentBalance = $prevBalance;
        foreach ($subsequentEntries as $nextEntry) {
            $currentBalance = $currentBalance + $nextEntry['quantity_in'] - $nextEntry['quantity_out'];
            
            $updateStmt = $this->conn->prepare("
                UPDATE stock_accounting 
                SET balance = ?
                WHERE id = ?
            ");
            $updateStmt->bind_param("di", $currentBalance, $nextEntry['id']);
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update subsequent entry balances');
            }
        }
        
        // Update the record's status
        $this->updateRecordStatus($entry['record_id']);
        
        $this->conn->commit();
        return ['success' => true];
        
    } catch (Exception $e) {
        $this->conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
    
    /**
     * Count total records for a user
     */
   // models/record.php - Update the count method
public function count($userId, $stockTypeId = null) {
    $query = "SELECT COUNT(*) as count FROM records WHERE user_id = ?";
    $types = "i";
    $params = [$userId];

    if ($stockTypeId !== null) {
        $query .= " AND stock_type_id = ?";
        $types .= "i";
        $params[] = $stockTypeId;
    }

    $stmt = $this->conn->prepare($query);
    
    // Bind parameters dynamically
    $bindParams = [&$types];
    foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key];
    }
    
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['count'];
    }
    
    return 0;
}
    
    /**
     * Check if code exists
     * Note: This method now always returns false to allow duplicate codes
     */
    public function codeExists($code, $userId, $excludeId = null) {
        return false; // Always return false to allow duplicate codes
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
                  WHERE record_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
}
?>