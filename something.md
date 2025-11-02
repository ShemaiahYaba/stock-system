Perfect — that’s the right mindset. Let’s keep this surgical and semantic.
Here’s exactly what needs to change in your **codebase** to map cleanly to the new SQL schema (no fluff, just direct mapping work):

---

## 🧩 1. Database Connection Layer

**Files affected:** `config/db.php` or your existing DB connector file.

- Ensure connection references the **new schema names**:

  ```php
  $conn = new PDO("mysql:host=localhost;dbname=stock_system;charset=utf8mb4", "root", "");
  ```

- Remove or update any legacy table names like `records`, `record_details`, etc.
  These should now point to:

  - `users`
  - `stock_types`
  - `records`
  - `stock_accounting`

---

## 🧱 2. Model or Data Access Functions

If you’re using raw PHP models (like `models/record.php`, `models/stock.php`), update queries semantically as follows:

### ✅ `records` queries

Replace:

```sql
SELECT * FROM records WHERE user_id = ?
```

With:

```sql
SELECT r.*, s.name AS stock_type_name
FROM records r
JOIN stock_types s ON s.id = r.stock_type_id
WHERE r.user_id = ?
```

### ✅ Insert logic

Old inserts into `records` need to **call the stored procedure** now:

```php
CALL create_record_with_opening(:user_id, :stock_type_id, :code, :color, :net_weight, :gauge, :opening_meters, :remarks);
```

So instead of manually inserting into `records` and then `stock_accounting`,
you now **only call the procedure** — it handles both.

---

## ⚙️ 3. Stock Accounting Page Logic

When loading stock accounting:

### Replace this kind of query:

```sql
SELECT * FROM stock_accounting WHERE record_id = ?
```

With:

```sql
SELECT sa.*, r.code, s.name AS stock_type_name
FROM stock_accounting sa
JOIN records r ON r.id = sa.record_id
JOIN stock_types s ON s.id = r.stock_type_id
WHERE sa.record_id = ?
ORDER BY sa.entry_date DESC;
```

This ensures every entry now pulls type + record metadata.

---

## 🔄 4. Update Transactions Logic

For the “inflow/outflow” operations:

### Replace the previous balance logic

If your old code manually adjusted `no_of_meters`, remove that logic.
That’s now handled by database triggers.

You just insert a new transaction:

```sql
INSERT INTO stock_accounting (record_id, entry_date, transaction_type, quantity_in, quantity_out, remarks)
VALUES (?, CURDATE(), ?, ?, ?, ?);
```

Then **read back the new record** to display updated balances.
The trigger auto-updates:

- `records.no_of_meters`
- `records.sales_status` (to `'Out of Stock'` when 0)

---

## 🧮 5. Enum & Status Mapping

Anywhere you display or filter by status:

Old:

```php
WHERE sales_status = 'available'
```

New (reflect enum):

```php
WHERE sales_status = 'Factory Use'
```

…and handle the automatic shift to `'Out of Stock'`.

---

## 🪟 6. Frontend (if applicable)

If your modal or sidebar fetches:

- **Opening balance:** now from `records.no_of_meters`
- **Current balance:** still `records.no_of_meters`
- **Ledger:** from `stock_accounting` entries

Ensure UI labels reflect:

- “Factory Use” = Active
- “Out of Stock” = Depleted

---

## 🧠 7. Stock-Type Isolation

Your sidebar or stock menu should filter by `stock_type_id`:

```php
SELECT * FROM records WHERE stock_type_id = :id ORDER BY created_at DESC;
```

You can seed `stock_types` table with:

```sql
INSERT INTO stock_types (name) VALUES ('Aluminium'), ('Alloy Steel'), ('Kzinc');
```

---

## ✅ 8. Optional Maintenance

Clean up any references to:

- `record_details` (merged into `stock_accounting`)
- `type` fields (now standardized as `stock_type_id`)
- `balance` columns (no longer needed; use `records.no_of_meters`)

---

If you want, I can now write out **the exact instructions you can feed to your AI agent** —
basically a machine-readable brief of these code actions (step-by-step edits it can apply to your repo).
Do you want me to generate that next?
