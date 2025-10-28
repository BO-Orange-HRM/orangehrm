-- ==========================================================
--  Payroll Module - Database Schema (OrangeHRM 5.7)
--  Author: Nimrod
--  Date: 2025-10-22
-- ==========================================================

-- 1. Salary Components Master Table
CREATE TABLE ohrm_salary_component
(
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    component_name     VARCHAR(255) NOT NULL,
    component_type     ENUM('earning', 'deduction', 'benefit') NOT NULL,
    calculation_method ENUM('fixed', 'percentage', 'formula') DEFAULT 'fixed',
    default_amount     DECIMAL(15, 2) DEFAULT 0.00,
    is_taxable         TINYINT(1) DEFAULT 1,
    display_order      INT            DEFAULT 0,
    is_active          TINYINT(1) DEFAULT 1,
    created_at         DATETIME       DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX              idx_component_name (component_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Employee Salary Configuration
CREATE TABLE ohrm_employee_salary_config
(
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    employee_id         INT            NOT NULL,
    basic_salary        DECIMAL(15, 2) NOT NULL,
    currency            VARCHAR(10) DEFAULT 'USD',
    payment_frequency   ENUM('monthly','bi-weekly','weekly') DEFAULT 'monthly',
    effective_from_date DATE           NOT NULL,
    effective_to_date   DATE        DEFAULT NULL,
    pay_grade           VARCHAR(50) DEFAULT NULL,
    is_active           TINYINT(1) DEFAULT 1,
    created_by          INT         DEFAULT NULL,
    created_date        DATETIME    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES hs_hr_employee (emp_number) ON DELETE CASCADE,
    INDEX               idx_employee_id (employee_id),
    INDEX               idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Employee Salary Components
CREATE TABLE ohrm_employee_salary_component
(
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    salary_config_id    INT  NOT NULL,
    component_id        INT  NOT NULL,
    amount              DECIMAL(15, 2) DEFAULT 0.00,
    percentage          DECIMAL(5, 2)  DEFAULT NULL,
    calculation_basis   ENUM('gross','basic','custom') DEFAULT 'basic',
    effective_from_date DATE NOT NULL,
    effective_to_date   DATE           DEFAULT NULL,
    is_active           TINYINT(1) DEFAULT 1,
    FOREIGN KEY (salary_config_id) REFERENCES ohrm_employee_salary_config (id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES ohrm_salary_component (id),
    INDEX               idx_salary_config (salary_config_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Payroll Period
CREATE TABLE ohrm_payroll_period
(
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    period_name        VARCHAR(100) NOT NULL,
    start_date         DATE         NOT NULL,
    end_date           DATE         NOT NULL,
    payment_date       DATE           DEFAULT NULL,
    status             ENUM('draft','processing','completed','cancelled') DEFAULT 'draft',
    total_employees    INT            DEFAULT 0,
    total_gross_amount DECIMAL(15, 2) DEFAULT 0.00,
    total_net_amount   DECIMAL(15, 2) DEFAULT 0.00,
    processed_by       INT            DEFAULT NULL,
    processed_date     DATETIME       DEFAULT NULL,
    approved_by        INT            DEFAULT NULL,
    approved_date      DATETIME       DEFAULT NULL,
    INDEX              idx_period_name (period_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Payroll Transactions
CREATE TABLE ohrm_payroll_transaction
(
    id               INT AUTO_INCREMENT PRIMARY KEY,
    period_id        INT NOT NULL,
    employee_id      INT NOT NULL,
    salary_config_id INT            DEFAULT NULL,
    gross_salary     DECIMAL(15, 2) DEFAULT 0.00,
    total_earnings   DECIMAL(15, 2) DEFAULT 0.00,
    total_deductions DECIMAL(15, 2) DEFAULT 0.00,
    net_salary       DECIMAL(15, 2) DEFAULT 0.00,
    payment_method   ENUM('bank','check','cash') DEFAULT 'bank',
    payment_status   ENUM('pending','paid','failed') DEFAULT 'pending',
    payment_date     DATETIME       DEFAULT NULL,
    worked_days      DECIMAL(5, 2)  DEFAULT 0.00,
    absent_days      DECIMAL(5, 2)  DEFAULT 0.00,
    leave_days       DECIMAL(5, 2)  DEFAULT 0.00,
    overtime_hours   DECIMAL(5, 2)  DEFAULT 0.00,
    created_date     DATETIME       DEFAULT CURRENT_TIMESTAMP,
    updated_date     DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (period_id) REFERENCES ohrm_payroll_period (id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES hs_hr_employee (emp_number),
    FOREIGN KEY (salary_config_id) REFERENCES ohrm_employee_salary_config (id),
    INDEX            idx_period_employee (period_id, employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Payroll Transaction Details
CREATE TABLE ohrm_payroll_transaction_detail
(
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id       INT NOT NULL,
    component_id         INT            DEFAULT NULL,
    component_name       VARCHAR(255),
    component_type       ENUM('earning','deduction','benefit') DEFAULT 'earning',
    calculated_amount    DECIMAL(15, 2) DEFAULT 0.00,
    calculation_notes    TEXT           DEFAULT NULL,
    is_manual_adjustment TINYINT(1) DEFAULT 0,
    adjusted_by          INT            DEFAULT NULL,
    adjustment_reason    VARCHAR(255)   DEFAULT NULL,
    FOREIGN KEY (transaction_id) REFERENCES ohrm_payroll_transaction (id) ON DELETE CASCADE,
    FOREIGN KEY (component_id) REFERENCES ohrm_salary_component (id),
    INDEX                idx_transaction (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Payslips
CREATE TABLE ohrm_payslip
(
    id                INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id    INT NOT NULL,
    payslip_number    VARCHAR(100) UNIQUE,
    generated_date    DATETIME DEFAULT CURRENT_TIMESTAMP,
    file_path         VARCHAR(255),
    file_name         VARCHAR(255),
    email_sent        TINYINT(1) DEFAULT 0,
    email_sent_date   DATETIME DEFAULT NULL,
    employee_viewed   TINYINT(1) DEFAULT 0,
    viewed_date       DATETIME DEFAULT NULL,
    pdf_generated     TINYINT(1) DEFAULT 0,
    generation_status ENUM('pending','completed','failed') DEFAULT 'pending',
    FOREIGN KEY (transaction_id) REFERENCES ohrm_payroll_transaction (id) ON DELETE CASCADE,
    INDEX             idx_payslip_number (payslip_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Audit Log (Generic)
CREATE TABLE ohrm_payroll_audit_log
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    table_name  VARCHAR(100),
    record_id   INT,
    action_type ENUM('insert','update','delete'),
    user_id     INT         DEFAULT NULL,
    timestamp   DATETIME    DEFAULT CURRENT_TIMESTAMP,
    old_values  JSON        DEFAULT NULL,
    new_values  JSON        DEFAULT NULL,
    ip_address  VARCHAR(50) DEFAULT NULL,
    INDEX       idx_table_record (table_name, record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
