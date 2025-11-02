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
        $query = "CREATE TABLE IF NOT EXISTS `stock_accounting` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `record_id` INT NOT NULL,
            `entry_date` DATE NOT NULL,
            `transaction_type` ENUM('inflow','outflow') NOT NULL,
            `quantity_in` DECIMAL(14,2) DEFAULT 0.00,
            `quantity_out` DECIMAL(14,2) DEFAULT 0.00,
            `balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            `remarks` VARCHAR(255),
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`record_id`) REFERENCES `records`(`id`) ON DELETE CASCADE,
            INDEX (`record_id`),
            INDEX (`entry_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        return $this->conn->query($query);
    }
    
    /**
     * Get all accounting entries for a specific record
     */
    public function getByRecordId($recordId, $userId) {
        // Verify record belongs to user
        $stmt = $this->conn->prepare("SELECT id FROM records WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $recordId, $userId);
        $stmt->execute();
        $verifyResult = $stmt->get_result();
        
        if (!$verifyResult || $verifyResult->num_rows === 0) {
            return [];
        }
        
        $stmt = $this->conn->prepare("SELECT * FROM stock_accounting 
                                    WHERE record_id = ? 
                                    ORDER BY entry_date DESC, created_at DESC");
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC) ?: [];
        }
        
        return [];
    }
    
    /**
     * Get all accounting entries for all user's records
     */
    public function getAllByUserId($userId) {
        $stmt = $this->conn->prepare("SELECT sa.*, r.code, r.color 
                                    FROM stock_accounting sa
                                    INNER JOIN records r ON sa.record_id = r.id
                                    WHERE r.user_id = ?
                                    ORDER BY sa.entry_date DESC, sa.created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC) ?: [];
        }
        
        return [];
    }
    
    /**
     * Get single accounting entry by ID
     */
    public function getById($id, $userId) {
        $stmt = $this->conn->prepare("SELECT sa.* 
                                    FROM stock_accounting sa
                                    INNER JOIN records r ON sa.record_id = r.id
                                    WHERE sa.id = ? AND r.user_id = ?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    public function getLatestBalance($recordId, $userId = null) {
    // First, get the stock_type_id for this record
    $typeStmt = $this->conn->prepare("
        SELECT stock_type_id 
        FROM records 
        WHERE id = ?" . ($userId !== null ? " AND user_id = ?" : "")
    );
    
    if ($userId !== null) {
        $typeStmt->bind_param("ii", $recordId, $userId);
    } else {
        $typeStmt->bind_param("i", $recordId);
    }
    
    $typeStmt->execute();
    $typeResult = $typeStmt->get_result();
    
    if (!$typeResult || $typeResult->num_rows === 0) {
        return 0.00;
    }
    
    $record = $typeResult->fetch_assoc();
    $stockTypeId = $record['stock_type_id'];
    
    // Now get the latest balance for this record and stock type
    if ($userId !== null) {
        $stmt = $this->conn->prepare("
            SELECT sa.balance 
            FROM stock_accounting sa
            INNER JOIN records r ON sa.record_id = r.id
            WHERE sa.record_id = ? AND r.user_id = ? AND r.stock_type_id = ?
            ORDER BY sa.entry_date DESC, sa.id DESC
            LIMIT 1
        ");
        $stmt->bind_param("iii", $recordId, $userId, $stockTypeId);
    } else {
        $stmt = $this->conn->prepare("
            SELECT balance 
            FROM stock_accounting 
            WHERE record_id = ? 
            ORDER BY entry_date DESC, id DESC
            LIMIT 1
        ");
        $stmt->bind_param("i", $recordId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return (float)$row['balance'];
    }
    
    // If no accounting entries exist, return 0 instead of the record's current meter value
    // to prevent double-counting the initial value
    return 0.00;
}
    
    /**
     * Get stock movement history for a record with pagination
     */
   public function getMovementHistory($recordId, $userId, $page = 1, $perPage = 20) {
    $offset = ($page - 1) * $perPage;
    
    // Get the stock_type_id for this record
    $typeStmt = $this->conn->prepare("
        SELECT stock_type_id 
        FROM records 
        WHERE id = ? AND user_id = ?
    ");
    $typeStmt->bind_param("ii", $recordId, $userId);
    $typeStmt->execute();
    $typeResult = $typeStmt->get_result();
    
    if (!$typeResult || $typeResult->num_rows === 0) {
        return ['data' => [], 'pagination' => [
            'total' => 0,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => 0
        ]];
    }
    
    $record = $typeResult->fetch_assoc();
    $stockTypeId = $record['stock_type_id'];
    
    // Get total count for pagination
    $countStmt = $this->conn->prepare("
        SELECT COUNT(*) as total 
        FROM stock_accounting sa
        INNER JOIN records r ON sa.record_id = r.id
        WHERE sa.record_id = ? AND r.user_id = ? AND r.stock_type_id = ?
    ");
    $countStmt->bind_param("iii", $recordId, $userId, $stockTypeId);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Get paginated results
    $stmt = $this->conn->prepare("
        SELECT sa.*, 
               CONCAT(u.name, ' (', u.email, ')') as user_name
        FROM stock_accounting sa
        INNER JOIN records r ON sa.record_id = r.id
        INNER JOIN users u ON r.user_id = u.id
        WHERE sa.record_id = ? AND r.user_id = ? AND r.stock_type_id = ?
        ORDER BY sa.entry_date DESC, sa.id DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iiiii", $recordId, $userId, $stockTypeId, $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $entries = [];
    if ($result) {
        $entries = $result->fetch_all(MYSQLI_ASSOC);
    }
    
    return [
        'data' => $entries,
        'pagination' => [
            'total' => (int)$total,
            'page' => (int)$page,
            'per_page' => (int)$perPage,
            'total_pages' => (int)ceil($total / $perPage)
        ]
    ];
}
    
    /**
     * Get stock summary for all records with accounting totals
     */
    public function getStockSummary($userId) {
        // First, get all records for the user
        $recordsStmt = $this->conn->prepare("
            SELECT r.id as record_id, r.code, r.color, r.no_of_meters, r.sales_status,
                   st.name as stock_type
            FROM records r
            INNER JOIN stock_types st ON r.stock_type_id = st.id
            WHERE r.user_id = ?
            ORDER BY r.id
        ");
        $recordsStmt->bind_param("i", $userId);
        $recordsStmt->execute();
        $recordsResult = $recordsStmt->get_result();
        
        $summary = [];
        
        if ($recordsResult) {
            while ($record = $recordsResult->fetch_assoc()) {
                // Get accounting totals for this record
                $accountingStmt = $this->conn->prepare("
                    SELECT 
                        COALESCE(SUM(CASE WHEN transaction_type = 'inflow' THEN quantity_in ELSE 0 END), 0) as total_in,
                        COALESCE(SUM(CASE WHEN transaction_type = 'outflow' THEN quantity_out ELSE 0 END), 0) as total_out,
                        COALESCE(SUM(quantity_in - quantity_out), 0) as net_balance
                    FROM stock_accounting
                    WHERE record_id = ?
                ");
                $accountingStmt->bind_param("i", $record['record_id']);
                $accountingStmt->execute();
                $accountingResult = $accountingStmt->get_result();
                $accounting = $accountingResult->fetch_assoc();
                
                $summary[] = [
                    'record_id' => $record['record_id'],
                    'code' => $record['code'],
                    'color' => $record['color'],
                    'stock_type' => $record['stock_type'],
                    'sales_status' => $record['sales_status'],
                    'no_of_meters' => $record['no_of_meters'],
                    'total_in' => $accounting ? (float)$accounting['total_in'] : 0,
                    'total_out' => $accounting ? (float)$accounting['total_out'] : 0,
                    'balance' => $accounting ? (float)$accounting['net_balance'] : 0
                ];
            }
        }
        
        return $summary;
    }
    
    /**
     * Create new accounting entry
     */
    /**
     * Create new stock accounting entry
     * 
     * @param array $data [
     *   'record_id' => int,
     *   'entry_date' => 'YYYY-MM-DD',
     *   'transaction_type' => 'inflow'|'outflow',
     *   'quantity' => float,
     *   'remarks' => string
     * ]
     * @return array [success, id|message]
     */
   public function create($data) {
    // Begin transaction
    $this->conn->begin_transaction();
    
    try {
        // Get the stock_type_id for this record
        $typeStmt = $this->conn->prepare("
            SELECT stock_type_id 
            FROM records 
            WHERE id = ? AND user_id = ?
        ");
        $typeStmt->bind_param("ii", $data['record_id'], $data['user_id']);
        $typeStmt->execute();
        $typeResult = $typeStmt->get_result();
        
        if (!$typeResult || $typeResult->num_rows === 0) {
            throw new Exception('Record not found or access denied');
        }
        
        $record = $typeResult->fetch_assoc();
        $stockTypeId = $record['stock_type_id'];
        
        // Get current balance for this record and stock type
        $currentBalance = $this->getLatestBalance($data['record_id'], $data['user_id']);
        
        // Calculate new balance
        $quantityIn = ($data['transaction_type'] === 'inflow') ? $data['quantity'] : 0;
        $quantityOut = ($data['transaction_type'] === 'outflow') ? $data['quantity'] : 0;
        $newBalance = $currentBalance + $quantityIn - $quantityOut;
        
        // Prepare the SQL query with placeholders
        $stmt = $this->conn->prepare("INSERT INTO stock_accounting 
            (record_id, entry_date, transaction_type, quantity_in, quantity_out, balance, remarks) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
            
        if ($stmt === false) {
            throw new Exception('Failed to prepare statement: ' . $this->conn->error);
        }
        
        // Handle nullable remarks
        $remarks = $data['remarks'] ?? null;
        
        // Bind parameters with proper null handling
        $stmt->bind_param("issddds",
            $data['record_id'],
            $data['entry_date'],
            $data['transaction_type'],
            $quantityIn,
            $quantityOut,
            $newBalance,
            $remarks
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute statement: ' . $stmt->error);
        }
        
        $entryId = $this->conn->insert_id;
        
        // Update the record's current balance and status
        $this->updateRecordStatus($data['record_id']);
        
        $this->conn->commit();
        return ['success' => true, 'id' => $entryId];
        
    } catch (Exception $e) {
        $this->conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
    
    /**
     * Update record's status based on accounting entries
     */
    private function updateRecordStatus($recordId) {
        try {
            // Get the current balance and entry count
            $query = "
                SELECT 
                    COALESCE(SUM(quantity_in - quantity_out), 0) as current_balance
                FROM stock_accounting 
                WHERE record_id = ?";
                
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $this->conn->error);
            }
            
            $stmt->bind_param("i", $recordId);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute statement: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $data = $result ? $result->fetch_assoc() : null;
            
            $currentBalance = $data ? max(0, (float)$data['current_balance']) : 0;
            
            // Determine status
            // Default status is 'Factory Use' regardless of inflows/outflows
            $status = 'Factory Use';
            // Only change to 'Sold Out' when balance is zero or negative
            if ($currentBalance <= 0) {
                $status = 'Sold Out';
            }
            
            // Update the record
            $updateQuery = "
                UPDATE records 
                SET no_of_meters = ?,
                    sales_status = ?,
                    updated_at = NOW()
                WHERE id = ?";
                
            $updateStmt = $this->conn->prepare($updateQuery);
            if (!$updateStmt) {
                throw new Exception('Failed to prepare update statement: ' . $this->conn->error);
            }
            
            $updateStmt->bind_param("dsi", $currentBalance, $status, $recordId);
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update record: ' . $updateStmt->error);
            }
            
            return $currentBalance;
            
        } catch (Exception $e) {
            error_log('Error in updateRecordStatus: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update an existing stock accounting entry
     * Note: This is a complex operation that requires recalculating subsequent balances
     */
    public function update($id, $userId, $data) {
        // Verify entry belongs to user's record and get current data
        $currentEntry = $this->getById($id, $userId);
        if (!$currentEntry) {
            return ['success' => false, 'message' => 'Entry not found or access denied'];
        }
        
        $this->conn->begin_transaction();
        
        try {
            // Get all entries after this one to recalculate balances
            $stmt = $this->conn->prepare("
                SELECT id, quantity_in, quantity_out, balance 
                FROM stock_accounting 
                WHERE record_id = ? AND id > ? 
                ORDER BY entry_date, id
                FOR UPDATE
            ");
            $stmt->bind_param("ii", $currentEntry['record_id'], $id);
            $stmt->execute();
            $subsequentEntries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Calculate new balance for this entry
            $prevBalance = $this->getBalanceBefore($currentEntry['record_id'], $currentEntry['id']);
            $newQuantityIn = ($data['transaction_type'] === 'inflow') ? $data['quantity'] : 0;
            $newQuantityOut = ($data['transaction_type'] === 'outflow') ? $data['quantity'] : 0;
            $newBalance = $prevBalance + $newQuantityIn - $newQuantityOut;
            
            // Update the current entry
            $updateStmt = $this->conn->prepare("
                UPDATE stock_accounting 
                SET entry_date = ?,
                    transaction_type = ?,
                    quantity_in = ?,
                    quantity_out = ?,
                    balance = ?,
                    remarks = ?,
                    updated_at = NOW()
                WHERE id = ?
            
            ");
            $updateStmt->bind_param("ssdddssi",
                $data['entry_date'],
                $data['transaction_type'],
                $newQuantityIn,
                $newQuantityOut,
                $newBalance,
                $data['remarks'] ?? null,
                $id
            );
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update accounting entry');
            }
            
            // Recalculate balances for subsequent entries
            $currentBalance = $newBalance;
            foreach ($subsequentEntries as $entry) {
                $currentBalance = $currentBalance + $entry['quantity_in'] - $entry['quantity_out'];
                
                $updateSubsequent = $this->conn->prepare("
                    UPDATE stock_accounting 
                    SET balance = ?
                    WHERE id = ?
                
                ");
                $updateSubsequent->bind_param("di", $currentBalance, $entry['id']);
                if (!$updateSubsequent->execute()) {
                    throw new Exception('Failed to update subsequent entry balances');
                }
            }
            
            // Update the record's status
            $this->updateRecordStatus($currentEntry['record_id']);
            
            $this->conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get the balance before a specific entry
     */
    private function getBalanceBefore($recordId, $entryId) {
        $stmt = $this->conn->prepare("
            SELECT balance 
            FROM stock_accounting 
            WHERE record_id = ? AND id < ? 
            ORDER BY entry_date DESC, id DESC 
            LIMIT 1
        
        ");
        $stmt->bind_param("ii", $recordId, $entryId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        return $result ? (float)$result['balance'] : 0.00;
    }
    
    /**
     * Delete accounting entry
     */
    /**
     * Delete an accounting entry and recalculate subsequent balances
     */
    public function delete($id, $userId) {
        // Verify entry belongs to user's record and get record_id
        $entry = $this->getById($id, $userId);
        if (!$entry) {
            return ['success' => false, 'message' => 'Entry not found or access denied'];
        }
        
        $this->conn->begin_transaction();
        
        try {
            // Get all entries after this one to recalculate balances
            $stmt = $this->conn->prepare("
                SELECT id, quantity_in, quantity_out, balance 
                FROM stock_accounting 
                WHERE record_id = ? AND id > ? 
                ORDER BY entry_date, id
                FOR UPDATE
            
            ");
            $stmt->bind_param("ii", $entry['record_id'], $id);
            $stmt->execute();
            $subsequentEntries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get the balance before this entry
            $prevBalance = $this->getBalanceBefore($entry['record_id'], $id);
            
            // Delete the entry
            $deleteStmt = $this->conn->prepare("DELETE FROM stock_accounting WHERE id = ?");
            $deleteStmt->bind_param("i", $id);
            
            if (!$deleteStmt->execute() || $deleteStmt->affected_rows === 0) {
                throw new Exception('Failed to delete accounting entry');
            }
            
            // Recalculate balances for subsequent entries
            $currentBalance = $prevBalance;
            foreach ($subsequentEntries as $entry) {
                $currentBalance = $currentBalance + $entry['quantity_in'] - $entry['quantity_out'];
                
                $updateStmt = $this->conn->prepare("
                    UPDATE stock_accounting 
                    SET balance = ?
                    WHERE id = ?
                
                ");
                $updateStmt->bind_param("di", $currentBalance, $entry['id']);
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
     * Get summary statistics for a record
     */
    public function getSummary($recordId, $userId) {
        $stmt = $this->conn->prepare("SELECT 
                                    COALESCE(SUM(quantity_in), 0) as total_in,
                                    COALESCE(SUM(quantity_out), 0) as total_out,
                                    (SELECT balance FROM stock_accounting 
                                     WHERE record_id = ? 
                                     ORDER BY entry_date DESC, created_at DESC LIMIT 1) as current_balance
                                    FROM stock_accounting sa
                                    INNER JOIN records r ON sa.record_id = r.id
                                    WHERE sa.record_id = ? AND r.user_id = ?");
        $stmt->bind_param("ii", $recordId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
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
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total 
                                    FROM stock_accounting sa
                                    INNER JOIN records r ON sa.record_id = r.id
                                    WHERE sa.record_id = ? AND r.user_id = ?");
        $stmt->bind_param("ii", $recordId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }
        
        return 0;
    }
}
?>