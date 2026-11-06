-- Factory Management System schema (MySQL 8+ / MariaDB 10.5+)
-- Repeatable: safe to run on an empty database more than once.
-- Source: SQL Server tables inferred from FactoryManagementSystem queries.
-- Password and PasswordHash are both stored so the desktop column names
-- (login reads Password; password reset writes PasswordHash) stay aligned.

CREATE TABLE IF NOT EXISTS Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    FullName VARCHAR(150) NOT NULL,
    Username VARCHAR(100) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    PasswordHash VARCHAR(255) NULL,
    Role VARCHAR(50) NOT NULL,
    IsActive TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_users_username (Username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS SystemAdmins (
    AdminID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(100) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    PasswordHash VARCHAR(255) NULL,
    UNIQUE KEY uq_admins_username (Username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS AuditLogs (
    LogID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(100) NOT NULL,
    Action VARCHAR(100) NOT NULL,
    Module VARCHAR(100) NOT NULL,
    Description VARCHAR(2000) NOT NULL,
    Status VARCHAR(200) NOT NULL,
    LogDate DATETIME NOT NULL,
    KEY ix_audit_date (LogDate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Workers (
    WorkerID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(30) NOT NULL,
    Role VARCHAR(50) NOT NULL,
    Salary DECIMAL(18,2) NOT NULL,
    CONSTRAINT ck_workers_salary CHECK (Salary >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS RawMaterial (
    MaterialID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Quantity DECIMAL(18,2) NOT NULL,
    Unit VARCHAR(30) NOT NULL,
    UnitPrice DECIMAL(18,2) NOT NULL,
    CONSTRAINT ck_material_qty CHECK (Quantity >= 0),
    CONSTRAINT ck_material_price CHECK (UnitPrice > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Payments (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    Amount DECIMAL(18,2) NOT NULL,
    Reason VARCHAR(200) NOT NULL,
    Date DATETIME NOT NULL,
    CONSTRAINT ck_payment_amount CHECK (Amount > 0),
    KEY ix_payments_date (Date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Products (
    ProductID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Unit VARCHAR(30) NOT NULL,
    UNIQUE KEY uq_products_name (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Production (
    ProductionID INT AUTO_INCREMENT PRIMARY KEY,
    ProductName VARCHAR(100) NOT NULL,
    Quantity DECIMAL(18,2) NOT NULL,
    Date DATETIME NOT NULL,
    CONSTRAINT ck_production_qty CHECK (Quantity >= 0),
    KEY ix_production_product (ProductName),
    KEY ix_production_date (Date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS MaterialUsage (
    UsageID INT AUTO_INCREMENT PRIMARY KEY,
    MaterialID INT NOT NULL,
    MaterialName VARCHAR(100) NOT NULL,
    QuantityUsed DECIMAL(18,2) NOT NULL,
    Date DATETIME NOT NULL,
    CONSTRAINT ck_usage_qty CHECK (QuantityUsed > 0),
    KEY ix_usage_material (MaterialID),
    KEY ix_usage_date (Date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Customers (
    CustomerID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerName VARCHAR(200) NOT NULL,
    Phone VARCHAR(50) NOT NULL DEFAULT '',
    Address VARCHAR(500) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Sales (
    SaleID INT AUTO_INCREMENT PRIMARY KEY,
    InvoiceNo VARCHAR(50) NOT NULL,
    CustomerID INT NOT NULL,
    SaleDate DATETIME NOT NULL,
    SubTotal DECIMAL(18,2) NOT NULL,
    Discount DECIMAL(18,2) NOT NULL,
    Tax DECIMAL(18,2) NOT NULL,
    GrandTotal DECIMAL(18,2) NOT NULL,
    PaymentStatus VARCHAR(30) NOT NULL,
    PaymentMethod VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_sales_invoice (InvoiceNo),
    KEY ix_sales_date (SaleDate),
    CONSTRAINT fk_sales_customer FOREIGN KEY (CustomerID) REFERENCES Customers (CustomerID),
    CONSTRAINT ck_sales_money CHECK (SubTotal >= 0 AND Discount >= 0 AND Tax >= 0 AND GrandTotal >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS SaleItems (
    SaleItemID INT AUTO_INCREMENT PRIMARY KEY,
    SaleID INT NOT NULL,
    ProductionID INT NOT NULL,
    ProductName VARCHAR(100) NOT NULL,
    Quantity DECIMAL(18,2) NOT NULL,
    UnitPrice DECIMAL(18,2) NOT NULL,
    TotalAmount DECIMAL(18,2) NOT NULL,
    CONSTRAINT fk_items_sale FOREIGN KEY (SaleID) REFERENCES Sales (SaleID),
    CONSTRAINT fk_items_production FOREIGN KEY (ProductionID) REFERENCES Production (ProductionID),
    CONSTRAINT ck_items_qty CHECK (Quantity > 0 AND UnitPrice >= 0 AND TotalAmount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS SalesPayment (
    SalesPaymentID INT AUTO_INCREMENT PRIMARY KEY,
    SaleID INT NOT NULL,
    AmountPaid DECIMAL(18,2) NOT NULL,
    PaymentMethod VARCHAR(50) NOT NULL,
    PaymentDate DATETIME NOT NULL,
    PaymentStatus VARCHAR(30) NOT NULL,
    KEY ix_salespayment_date (PaymentDate),
    CONSTRAINT fk_salespayment_sale FOREIGN KEY (SaleID) REFERENCES Sales (SaleID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS Returns (
    ReturnID INT AUTO_INCREMENT PRIMARY KEY,
    SaleID INT NOT NULL,
    SaleItemID INT NULL,
    ProductionID INT NOT NULL,
    Quantity DECIMAL(18,2) NOT NULL,
    RefundAmount DECIMAL(18,2) NOT NULL,
    RefundMethod VARCHAR(50) NOT NULL,
    Reason VARCHAR(100) NOT NULL,
    Notes VARCHAR(1000) NOT NULL DEFAULT '',
    ReturnDate DATETIME NOT NULL,
    KEY ix_returns_sale (SaleID),
    CONSTRAINT fk_returns_sale FOREIGN KEY (SaleID) REFERENCES Sales (SaleID),
    CONSTRAINT fk_returns_item FOREIGN KEY (SaleItemID) REFERENCES SaleItems (SaleItemID),
    CONSTRAINT fk_returns_production FOREIGN KEY (ProductionID) REFERENCES Production (ProductionID),
    CONSTRAINT ck_returns_qty CHECK (Quantity > 0 AND RefundAmount >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
