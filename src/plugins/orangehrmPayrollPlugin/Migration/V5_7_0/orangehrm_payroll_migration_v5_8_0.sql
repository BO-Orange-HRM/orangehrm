-- ==========================================================
-- OrangeHRM Payroll Migration (v5.8.0)
-- Direct SQL version for phpMyAdmin
-- ==========================================================

START TRANSACTION;

-- ==========================================================
-- 1. CREATE TABLES
-- ==========================================================

CREATE TABLE IF NOT EXISTS ohrm_pay_period
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)         NOT NULL,
    start_date DATE                 NOT NULL,
    end_date   DATE                 NOT NULL,
    is_closed  TINYINT(1) DEFAULT 0 NOT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS ohrm_payroll_period
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)   NOT NULL,
    start_date   DATE           NOT NULL,
    end_date     DATE           NOT NULL,
    frequency    VARCHAR(50)    NULL,
    status       VARCHAR(20)             DEFAULT 'draft',
    total_amount DECIMAL(15, 2) NULL,
    created_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME       NULL,
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS ohrm_payroll
(
    id              INT AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT        NOT NULL,
    pay_period_id   INT        NOT NULL,
    basic_salary    DECIMAL(15, 2) DEFAULT 0.00,
    gross_amount    DECIMAL(10, 2) DEFAULT 0.00,
    deductions      DECIMAL(10, 2) DEFAULT 0.00,
    net_amount      DECIMAL(10, 2) DEFAULT 0.00,
    status          VARCHAR(20)    DEFAULT 'pending',
    processed_at    DATETIME   NULL,
    currency_id     VARCHAR(6) NOT NULL,
    pay_period_code INT        NOT NULL,
    created_at      DATETIME       DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME   NULL,
    INDEX idx_payroll_period (pay_period_id),
    CONSTRAINT fk_employee FOREIGN KEY (employee_id)
        REFERENCES hs_hr_employee (emp_number) ON DELETE CASCADE,
    INDEX idx_payroll_pay_period (pay_period_code)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;



CREATE TABLE IF NOT EXISTS ohrm_payroll_item
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    payroll_id INT            NOT NULL,
    item_name  VARCHAR(100)   NOT NULL,
    item_type  VARCHAR(20)    NOT NULL,
    amount     DECIMAL(10, 2) NOT NULL,
    remarks    VARCHAR(255)   NULL,
    CONSTRAINT fk_payroll FOREIGN KEY (payroll_id)
        REFERENCES ohrm_payroll (id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS ohrm_payroll_config
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    key_name    VARCHAR(100) NOT NULL,
    key_value   VARCHAR(255) NOT NULL,
    description VARCHAR(255) NULL,
    updated_at  DATETIME     NULL,
    UNIQUE KEY uq_key_name (key_name)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4;

-- ==========================================================
-- 2. SEED PAYROLL MODULE AND SCREENS
-- ==========================================================

-- Create module if not exists
INSERT INTO ohrm_module (name, status, display_name)
SELECT 'payroll', 1, 'Payroll'
WHERE NOT EXISTS (SELECT 1 FROM ohrm_module WHERE name = 'payroll');

SET @module_id = (SELECT id
                  FROM ohrm_module
                  WHERE name = 'payroll');

-- Create screens
INSERT INTO ohrm_screen (name, module_id, action_url)
SELECT 'Payroll - Menu Module', @module_id, 'viewPayrollModule'
WHERE NOT EXISTS (SELECT 1 FROM ohrm_screen WHERE name = 'Payroll - Menu Module');

INSERT INTO ohrm_screen (name, module_id, action_url)
SELECT 'Payroll - dashboard', @module_id, 'viewPayrollDashboard'
WHERE NOT EXISTS (SELECT 1 FROM ohrm_screen WHERE name = 'Payroll - dashboard');

INSERT INTO ohrm_screen (name, module_id, action_url)
SELECT 'Payroll - Reports', @module_id, 'viewPayrollReports'
WHERE NOT EXISTS (SELECT 1 FROM ohrm_screen WHERE name = 'Payroll - Reports');

INSERT INTO ohrm_screen (name, module_id, action_url)
SELECT 'Payroll - Periods', @module_id, 'viewPayrollPeriods'
WHERE NOT EXISTS (SELECT 1 FROM ohrm_screen WHERE name = 'Payroll - Periods');

INSERT INTO ohrm_screen (name, module_id, action_url)
SELECT 'Payroll - Employee Overview', @module_id, 'employeePayrollOverview'
WHERE NOT EXISTS (SELECT 1 FROM ohrm_screen WHERE name = 'Payroll - Employee Overview');

INSERT INTO ohrm_screen (name, module_id, action_url)
SELECT 'Payroll - View Details', @module_id, 'viewPayrollDetails'
WHERE NOT EXISTS (SELECT 1 FROM ohrm_screen WHERE name = 'Payroll - View Details');

-- ==========================================================
-- 3. SEED API PERMISSIONS & DATA GROUPS
-- ==========================================================

-- Data groups
INSERT INTO ohrm_data_group (name, description, can_read, can_create, can_update, can_delete)
SELECT 'api_v2_payroll_dashboard', 'Payroll - Get Payroll Dashboard', 1, 0, 0, 0
WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group WHERE name = 'api_v2_payroll_dashboard');

INSERT INTO ohrm_data_group (name, description, can_read, can_create, can_update, can_delete)
SELECT 'api_v2_payroll_periods', 'Payroll - Get Post Put Delete Payroll Periods', 1, 1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group WHERE name = 'api_v2_payroll_periods');

INSERT INTO ohrm_data_group (name, description, can_read, can_create, can_update, can_delete)
SELECT 'api_v2_payroll_employee_overview', 'Payroll - Get Put Delete Employee Payroll Overview', 1, 0, 0, 0
WHERE NOT EXISTS (SELECT 1 FROM ohrm_data_group WHERE name = 'api_v2_payroll_employee_overview');

-- API Permissions
INSERT INTO ohrm_api_permission (module_id, data_group_id, api_name)
SELECT @module_id, id, 'OrangeHRM\\Payroll\\Api\\PayrollDashboardAPI'
FROM ohrm_data_group
WHERE name = 'api_v2_payroll_dashboard'
  AND NOT EXISTS (SELECT 1 FROM ohrm_api_permission WHERE data_group_id = ohrm_data_group.id);

INSERT INTO ohrm_api_permission (module_id, data_group_id, api_name)
SELECT @module_id, id, 'OrangeHRM\\Payroll\\Api\\PayrollPeriodAPI'
FROM ohrm_data_group
WHERE name ='api_v2_payroll_periods'
  AND NOT EXISTS (SELECT 1 FROM ohrm_api_permission WHERE data_group_id = ohrm_data_group.id);

INSERT INTO ohrm_api_permission (module_id, data_group_id, api_name)
SELECT @module_id, id, 'OrangeHRM\\Payroll\\Api\\EmployeePayrollOverviewAPI'
FROM ohrm_data_group
WHERE name = 'api_v2_payroll_employee_overview'
  AND NOT EXISTS (SELECT 1 FROM ohrm_api_permission WHERE data_group_id = ohrm_data_group.id);


-- Create a temporary table to store API definitions
CREATE TEMPORARY TABLE IF NOT EXISTS tmp_payroll_apis
(
    api_name    VARCHAR(255),
    name        VARCHAR(255),
    description VARCHAR(255)
);

INSERT INTO tmp_payroll_apis (api_name, name, description)
VALUES ('OrangeHRM\\Payroll\\Api\\PayrollDashboardAPI', 'api_v2_payroll_dashboard', 'Payroll - Get Payroll Dashboard'),
       ('OrangeHRM\\Payroll\\Api\\PayrollPeriodAPI', 'api_v2_payroll_periods',
        'Payroll - Get Post Put Delete Payroll Periods'),
       ('OrangeHRM\\Payroll\\Api\\PayrollPeriodAPI', 'api_v2_payroll_periods_get_one',
        'Payroll - Get Put Delete One Payroll Period'),
       ('OrangeHRM\\Payroll\\Api\\EmployeePayrollOverviewAPI', 'api_v2_payroll_employee_overview',
        'Payroll - Get Put Delete Employee Payroll Overview'),
       ('OrangeHRM\\Payroll\\Api\\PayrollPeriodAPI', 'api_v2_payroll_period',
        'Payroll - Get Put Delete Payroll Period');

-- =====================================
-- Insert into ohrm_data_group if not exists
-- =====================================
INSERT INTO ohrm_data_group (name, description, can_read, can_create, can_update, can_delete)
SELECT t.name, t.description, 1, 0, 0, 0
FROM tmp_payroll_apis t
WHERE NOT EXISTS (SELECT 1
                  FROM ohrm_data_group d
                  WHERE d.name = t.name);

-- =====================================
-- Insert into ohrm_api_permission if not exists
-- =====================================
-- ⚠️ Replace {YOUR_MODULE_ID} below with the actual module ID for Payroll
SET @moduleId = (SELECT id
                 FROM ohrm_module
                 WHERE name = 'payroll'
                 LIMIT 1);

INSERT INTO ohrm_api_permission (module_id, data_group_id, api_name)
SELECT
    @moduleId,
    d.id,
    t.api_name
FROM tmp_payroll_apis t
         JOIN ohrm_data_group d ON d.name = t.name
ON DUPLICATE KEY UPDATE
                     module_id = VALUES(module_id),
                     data_group_id = VALUES(data_group_id);


-- Drop the temporary table
DROP TEMPORARY TABLE tmp_payroll_apis;


-- ==========================================================
-- 4. MENU SETUP
-- ==========================================================

-- update the menu to with screen id
SET @parent_screen_menu_id = (Select id
                              FROM ohrm_screen
                              WHERE name = 'Payroll - Menu Module');

INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status, additional_params)
VALUES ('Payroll', @parent_screen_menu_id, NULL, 1, 250, 1, '{"icon":"payroll"}');

SET @parent_menu_id = LAST_INSERT_ID();

-- Create submenus
INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Dashboard', s.id, @parent_menu_id, 2, 300, 1
FROM ohrm_screen s
WHERE s.action_url = 'viewPayrollDashboard'
  AND NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE menu_title = 'Dashboard' AND parent_id = @parent_menu_id);

INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Employees Salaries', s.id, @parent_menu_id, 2, 500, 1
FROM ohrm_screen s
WHERE s.action_url = 'employeePayrollOverview'
  AND NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE menu_title = 'Employees Salaries' AND parent_id = @parent_menu_id);

INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Payroll Periods', s.id, @parent_menu_id, 2, 400, 1
FROM ohrm_screen s
WHERE s.action_url = 'viewPayrollPeriods'
  AND NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE menu_title = 'Payroll Periods' AND parent_id = @parent_menu_id);

INSERT INTO ohrm_menu_item (menu_title, screen_id, parent_id, level, order_hint, status)
SELECT 'Reports', s.id, @parent_menu_id, 2, 600, 1
FROM ohrm_screen s
WHERE s.action_url = 'viewPayrollReports'
  AND NOT EXISTS (SELECT 1 FROM ohrm_menu_item WHERE menu_title = 'Reports' AND parent_id = @parent_menu_id);

-- ======================================================
-- Step 4: Give Payroll Screens and APIs Permissions to Roles
-- ======================================================

-- 1️⃣ Get all assignable user roles
--    (This is implicit in the JOINs below)
--    SELECT * FROM ohrm_user_role WHERE is_assignable = 1;

-- 2️⃣ Assign screens to all assignable user roles
INSERT INTO ohrm_user_role_screen (user_role_id, screen_id, can_read, can_create, can_update, can_delete)
SELECT r.id AS user_role_id,
       s.id AS screen_id,
       1    AS can_read,
       0    AS can_create,
       0    AS can_update,
       0    AS can_delete
FROM ohrm_user_role r
         JOIN ohrm_screen s
              ON s.name IN (
                            'Payroll - Menu Module',
                            'Payroll - dashboard',
                            'Payroll - Reports',
                            'Payroll - Periods',
                            'Payroll - Employee Overview',
                            'Payroll - View Details'
                  )
WHERE r.is_assignable = 1
  AND NOT EXISTS (SELECT 1
                  FROM ohrm_user_role_screen urs
                  WHERE urs.user_role_id = r.id
                    AND urs.screen_id = s.id);

-- 3️⃣ Assign API permissions (Data Groups) to all assignable user roles
INSERT INTO ohrm_user_role_data_group (user_role_id, data_group_id, can_read, can_create, can_update, can_delete)
SELECT r.id  AS user_role_id,
       dg.id AS data_group_id,
       1     AS can_read,
       1     AS can_create,
       1     AS can_update,
       1     AS can_delete
FROM ohrm_user_role r
         JOIN ohrm_data_group dg
              ON dg.name IN (
                             'api_v2_payroll_dashboard',
                             'api_v2_payroll_periods',
                             'api_v2_payroll_periods_get_one',
                             'api_v2_payroll_employee_overview',
                             'api_v2_payroll_period'
                  )
WHERE r.is_assignable = 1
  AND NOT EXISTS (SELECT 1
                  FROM ohrm_user_role_data_group urdg
                  WHERE urdg.user_role_id = r.id
                    AND urdg.data_group_id = dg.id);

-- ==========================================================
-- COMMIT ALL CHANGES
-- ==========================================================

COMMIT;

-- ==========================================================
-- End of Payroll Migration (v5.8.0)
-- ==========================================================
