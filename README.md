# Factory Management System (web)

Web application for the MS Crete factory desktop system. Staff sign in as Owner, Manager, or Sales Person. Administrators are a separate account type and manage users, settings, reports, and backups.

The desktop application in `FactoryManagementSystem` remains the behavior reference. This repository does not modify it. `config/database.php` keeps `connection_string` empty until the local database is configured.

## Run locally

Requirements: PHP 8.1 or newer with PDO, and MySQL 8+ or MariaDB 10.5+ for the normal local server. The automated tests also run on SQLite.

1. Create an empty database, for example `factorydb`.
2. Import the schema:

   ```bash
   mysql -u fms -p factorydb < database/schema.mysql.sql
   ```

   Or, after the connection string is set, run `php bin/install.php`. That script refuses to run while the connection string is empty. To update an existing database, apply the schema again; it creates the material catalog and imports names and units from existing stock rows.
3. Edit `config/database.php` and set the PDO connection string, username, and password. Example:

   ```php
   'connection_string' => 'mysql:host=127.0.0.1;port=3306;dbname=factorydb;charset=utf8mb4',
   'username' => 'fms',
   'password' => 'secret',
   ```

4. Start the site from this directory:

   ```bash
   php -S localhost:8080 -t public public/router.php
   ```

5. Open `http://localhost:8080/admin/setup` and create the first administrator. Then sign in and add staff users. There is no default password in the repository.

Until the connection string is filled in, the sign-in page explains that the database is not configured.

## Tests

```bash
php tests/run.php
```

The test runner creates a temporary SQLite database through `FMS_CONNECTION_STRING`. It does not write credentials into `config/database.php`.

## What was ported

| Desktop area | Web route | Who can use it |
| --- | --- | --- |
| Staff login and role dashboards | `/` | Owner, Manager, Sales Person |
| Owner home, materials, products, payments, workers, reports | `/owner` | Owner |
| Factory home, production, material usage | `/factory` | Manager |
| Sales home, new sale, products, customers, history, returns, invoice | `/sales` | Sales Person |
| First administrator, admin login, users, admin accounts, reports, settings, backup | `/admin` | Administrator |

Persisted tables match the desktop queries: `Users`, `SystemAdmins`, `AuditLogs`, `Workers`, `Products`, `MaterialCatalog`, `RawMaterial`, `Payments`, `Production`, `MaterialUsage`, `Customers`, `Sales`, `SaleItems`, `SalesPayment`, and `Returns`. The `Products` table and material catalog start empty. The owner adds each product and its unit, and adds each raw material by entering its name, unit, quantity, and unit price. Nothing is inserted ahead of time.

## Adaptations

- The desktop app used SQL Server. This app uses PDO. Day-to-day setup is MySQL; tests use SQLite. There was no database script or production data in the desktop repository, so there is no data import.
- Passwords are stored with `password_hash`. A non-hash value in `Password` or `PasswordHash` is still accepted so a plaintext desktop row can sign in. Both columns are written together because the desktop login reads `Password` while password reset writes `PasswordHash`.
- Disabled staff accounts cannot sign in. The desktop login screen did not check `IsActive`, which made the disable action ineffective.
- A sale checks total stock, then takes quantity from the oldest production batches inside a transaction. The desktop screen checked the total and subtracted it from one arbitrary batch, which could drive that batch negative. Returns restore the batch that was sold. `Returns.SaleItemID` records the invoice line so the same line cannot be refunded twice.
- Damaged returns still do not go back into stock. Other return reasons do.
- Owner and sales reports include the whole selected end day. The desktop material and production queries used midnight-to-midnight `BETWEEN`, which dropped later times on the end date.
- A sale grand total cannot be negative.
- Database backup downloads a logical SQL snapshot into `storage/backups`. The desktop command was SQL Server `BACKUP DATABASE`, which a PHP process cannot run.
- Charts are HTML bars rather than ScottPlot. Invoices use the browser print dialog. The printed company name remains MS Crete.
- Adding raw material still creates a payment for quantity times unit price. Worker names stay letters and spaces, 3–30 characters. Production quantity stays from just above 0 through 10,000. Product low stock is at most 5,000; the sales dashboard low-stock count is at most 500.

## Not included

- Windows Forms layout, printer selection, and ScottPlot styling.
- A live SQL Server connection or a copy of an existing `FactoryDB`.
- Editing the database connection from the settings screen. The desktop screen displayed it; this app shows a redacted value and reads `config/database.php`.
