-- SQLite schema used by automated tests and optional local SQLite runs.
-- Column names match database/schema.mysql.sql.
-- SaleItemID on Returns records which invoice line was returned so a line
-- cannot be refunded twice. The desktop table did not have this column.

CREATE TABLE IF NOT EXISTS Users (
    UserID INTEGER PRIMARY KEY AUTOINCREMENT,
    FullName TEXT NOT NULL,
    Username TEXT NOT NULL UNIQUE,
    Password TEXT NOT NULL,
    PasswordHash TEXT NULL,
    Role TEXT NOT NULL,
    IsActive INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS SystemAdmins (
    AdminID INTEGER PRIMARY KEY AUTOINCREMENT,
    Username TEXT NOT NULL UNIQUE,
    Password TEXT NOT NULL,
    PasswordHash TEXT NULL
);

CREATE TABLE IF NOT EXISTS AuditLogs (
    LogID INTEGER PRIMARY KEY AUTOINCREMENT,
    Username TEXT NOT NULL,
    Action TEXT NOT NULL,
    Module TEXT NOT NULL,
    Description TEXT NOT NULL,
    Status TEXT NOT NULL,
    LogDate TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS Workers (
    WorkerID INTEGER PRIMARY KEY AUTOINCREMENT,
    Name TEXT NOT NULL,
    Role TEXT NOT NULL,
    Salary REAL NOT NULL CHECK (Salary >= 0)
);

CREATE TABLE IF NOT EXISTS RawMaterial (
    MaterialID INTEGER PRIMARY KEY AUTOINCREMENT,
    Name TEXT NOT NULL,
    Quantity REAL NOT NULL CHECK (Quantity >= 0),
    Unit TEXT NOT NULL,
    UnitPrice REAL NOT NULL CHECK (UnitPrice > 0)
);

CREATE TABLE IF NOT EXISTS MaterialCatalog (
    Name TEXT PRIMARY KEY COLLATE NOCASE,
    Unit TEXT NOT NULL
);

INSERT OR IGNORE INTO MaterialCatalog (Name, Unit)
SELECT MIN(TRIM(Name)), MAX(TRIM(Unit))
FROM RawMaterial
WHERE TRIM(Name) <> ''
GROUP BY UPPER(TRIM(Name));

CREATE TABLE IF NOT EXISTS Payments (
    PaymentID INTEGER PRIMARY KEY AUTOINCREMENT,
    Amount REAL NOT NULL CHECK (Amount > 0),
    Reason TEXT NOT NULL,
    Date TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS Products (
    ProductID INTEGER PRIMARY KEY AUTOINCREMENT,
    Name TEXT NOT NULL UNIQUE,
    Unit TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS Production (
    ProductionID INTEGER PRIMARY KEY AUTOINCREMENT,
    ProductName TEXT NOT NULL,
    Quantity REAL NOT NULL CHECK (Quantity >= 0),
    Date TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS MaterialUsage (
    UsageID INTEGER PRIMARY KEY AUTOINCREMENT,
    MaterialID INTEGER NOT NULL,
    MaterialName TEXT NOT NULL,
    QuantityUsed REAL NOT NULL CHECK (QuantityUsed > 0),
    Date TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS Customers (
    CustomerID INTEGER PRIMARY KEY AUTOINCREMENT,
    CustomerName TEXT NOT NULL,
    Phone TEXT NOT NULL DEFAULT '',
    Address TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS Sales (
    SaleID INTEGER PRIMARY KEY AUTOINCREMENT,
    InvoiceNo TEXT NOT NULL UNIQUE,
    CustomerID INTEGER NOT NULL REFERENCES Customers (CustomerID),
    SaleDate TEXT NOT NULL,
    SubTotal REAL NOT NULL CHECK (SubTotal >= 0),
    Discount REAL NOT NULL CHECK (Discount >= 0),
    Tax REAL NOT NULL CHECK (Tax >= 0),
    GrandTotal REAL NOT NULL CHECK (GrandTotal >= 0),
    PaymentStatus TEXT NOT NULL,
    PaymentMethod TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS SaleItems (
    SaleItemID INTEGER PRIMARY KEY AUTOINCREMENT,
    SaleID INTEGER NOT NULL REFERENCES Sales (SaleID),
    ProductionID INTEGER NOT NULL REFERENCES Production (ProductionID),
    ProductName TEXT NOT NULL,
    Quantity REAL NOT NULL CHECK (Quantity > 0),
    UnitPrice REAL NOT NULL CHECK (UnitPrice >= 0),
    TotalAmount REAL NOT NULL CHECK (TotalAmount >= 0)
);

CREATE TABLE IF NOT EXISTS SalesPayment (
    SalesPaymentID INTEGER PRIMARY KEY AUTOINCREMENT,
    SaleID INTEGER NOT NULL REFERENCES Sales (SaleID),
    AmountPaid REAL NOT NULL,
    PaymentMethod TEXT NOT NULL,
    PaymentDate TEXT NOT NULL,
    PaymentStatus TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS Returns (
    ReturnID INTEGER PRIMARY KEY AUTOINCREMENT,
    SaleID INTEGER NOT NULL REFERENCES Sales (SaleID),
    SaleItemID INTEGER NULL REFERENCES SaleItems (SaleItemID),
    ProductionID INTEGER NOT NULL REFERENCES Production (ProductionID),
    Quantity REAL NOT NULL CHECK (Quantity > 0),
    RefundAmount REAL NOT NULL CHECK (RefundAmount >= 0),
    RefundMethod TEXT NOT NULL,
    Reason TEXT NOT NULL,
    Notes TEXT NOT NULL DEFAULT '',
    ReturnDate TEXT NOT NULL
);
