<?php
/**
 * OrangeHRM Payroll DB Migration (v5.7.0)
 *
 * Creates core payroll tables, foreign keys, indexes, and seeds permissions/menu entries.
 */

namespace OrangeHRM\Payroll\Migration\V5_7_0;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\SchemaException;
use OrangeHRM\Installer\Util\V1\AbstractMigration;

class Migration extends AbstractMigration
{
    protected Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @throws SchemaException
     * @throws Exception
     */
    public function up(): void
    {
        $schemaManager = $this->conn->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        // 1️⃣ PAY PERIOD TABLE
        if (!in_array('ohrm_pay_period', $tables)) {
            $this->conn->executeStatement("
            CREATE TABLE ohrm_pay_period (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                is_closed TINYINT(1) DEFAULT 0 NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        }

        // 2️⃣ PAYROLL TABLE
        if (!in_array('ohrm_payroll', $tables)) {
            $this->conn->executeStatement("
            CREATE TABLE ohrm_payroll (
                id INT AUTO_INCREMENT PRIMARY KEY,
                employee_id INT NOT NULL,
                pay_period_id INT NOT NULL,
                gross_salary DECIMAL(10,2) DEFAULT 0.00,
                deductions DECIMAL(10,2) DEFAULT 0.00,
                net_salary DECIMAL(10,2) DEFAULT 0.00,
                status VARCHAR(20) DEFAULT 'pending',
                processed_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL,
                CONSTRAINT fk_employee FOREIGN KEY (employee_id) REFERENCES hs_hr_employee(emp_number) ON DELETE CASCADE,
                CONSTRAINT fk_pay_period FOREIGN KEY (pay_period_id) REFERENCES ohrm_pay_period(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        }

        // 3️⃣ PAYROLL ITEM TABLE
        if (!in_array('ohrm_payroll_item', $tables)) {
            $this->conn->executeStatement("
            CREATE TABLE ohrm_payroll_item (
                id INT AUTO_INCREMENT PRIMARY KEY,
                payroll_id INT NOT NULL,
                item_name VARCHAR(100) NOT NULL,
                item_type VARCHAR(20) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                remarks VARCHAR(255) NULL,
                CONSTRAINT fk_payroll FOREIGN KEY (payroll_id) REFERENCES ohrm_payroll(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        }

        // 4️⃣ PAYROLL CONFIG TABLE
        if (!in_array('ohrm_payroll_config', $tables)) {
            $this->conn->executeStatement("
            CREATE TABLE ohrm_payroll_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                key_name VARCHAR(100) NOT NULL,
                key_value VARCHAR(255) NOT NULL,
                description VARCHAR(255) NULL,
                updated_at DATETIME NULL,
                UNIQUE KEY uq_key_name (key_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        }

        // Then you can call your seedPermissionsAndMenu() as before
        $this->seedPermissionsAndMenu();
    }


    /**
     * Seed data groups/permissions/screens/menu for Payroll (idempotent).
     */
    private function seedPermissionsAndMenu(): void
    {
        echo "🔧 Seeding Payroll module permissions, screens, and menu...\n\n";

        // STEP 1: Create or get module
        $moduleId = $this->conn->fetchOne("SELECT id FROM ohrm_module WHERE name = ?", ['payroll']);
        if ($moduleId) {
            echo " ✓ Module already exists (ID: $moduleId)\n";
        } else {
            $this->conn->insert('ohrm_module', ['name' => 'payroll', 'status' => 1]);
            $moduleId = (int)$this->conn->lastInsertId();
            echo " ✓ Created module 'payroll' (ID: $moduleId)\n";
        }

        // STEP 2: Create screens
        echo "\nStep 2: Creating Screens...\n";
        $screens = [
            ['name' => 'payroll_dashboard', 'action_url' => 'viewPayrollDashboard'],
            ['name' => 'payroll_employee_salary', 'action_url' => 'viewEmployeeSalary'],
            ['name' => 'payroll_periods', 'action_url' => 'viewPayrollPeriods'],
            ['name' => 'payroll_reports', 'action_url' => 'viewPayrollReports'],
            ['name' => 'payroll_configuration', 'action_url' => 'viewPayrollConfiguration'],
        ];
        $screenIds = [];

        foreach ($screens as $screen) {
            $screenId = $this->conn->fetchOne("SELECT id FROM ohrm_screen WHERE name = ?", [$screen['name']]);
            if ($screenId) {
                echo " ✓ Screen '{$screen['name']}' already exists (ID: $screenId)\n";
            } else {
                $this->conn->insert('ohrm_screen', [
                    'name' => $screen['name'],
                    'module_id' => $moduleId,
                    'action_url' => $screen['action_url'],
                ]);
                $screenId = (int)$this->conn->lastInsertId();
                echo " ✓ Created screen '{$screen['name']}' (ID: $screenId)\n";
            }
            $screenIds[$screen['name']] = $screenId;
        }

        // STEP 3: Get all assignable user roles
        echo "\nStep 3: Getting User Roles...\n";
        $userRoles = $this->conn->fetchAllAssociative("SELECT id, name FROM ohrm_user_role WHERE is_assignable = 1");
        foreach ($userRoles as $role) {
            echo " ✓ Found role: {$role['name']} (ID: {$role['id']})\n";
        }

        // STEP 4: Create data groups and permissions
        echo "\nStep 4: Creating Data Groups and Permissions...\n";
        foreach ($screenIds as $screenName => $screenId) {
            $dataGroupName = strtoupper($screenName);

            $dataGroupId = $this->conn->fetchOne("SELECT id FROM ohrm_data_group WHERE name = ?", [$dataGroupName]);
            if (!$dataGroupId) {
                $this->conn->insert('ohrm_data_group', [
                    'name' => $dataGroupName,
                    'description' => "$screenName payroll access",
                    'can_read' => 1,
                    'can_create' => 1,
                    'can_update' => 1,
                    'can_delete' => 1,
                ]);
                $dataGroupId = (int)$this->conn->lastInsertId();
            }

            $apiName = "API_$screenName";
            $apiId = $this->conn->fetchOne("SELECT id FROM ohrm_api_permission WHERE api_name = ?", [$apiName]);
            if (!$apiId) {
                $this->conn->insert('ohrm_api_permission', [
                    'module_id' => $moduleId,
                    'data_group_id' => $dataGroupId,
                    'api_name' => $apiName,
                ]);
            }

            // Link data groups and screens to user roles
            foreach ($userRoles as $role) {
                $roleId = $role['id'];

                $existsDG = $this->conn->fetchOne(
                    "SELECT 1 FROM ohrm_user_role_data_group WHERE user_role_id = ? AND data_group_id = ?",
                    [$roleId, $dataGroupId]
                );
                if (!$existsDG) {
                    $this->conn->insert('ohrm_user_role_data_group', [
                        'user_role_id' => $roleId,
                        'data_group_id' => $dataGroupId,
                        'can_read' => 1,
                        'can_create' => 1,
                        'can_update' => 1,
                        'can_delete' => 1,
                    ]);
                    $this->conn->insert('ohrm_user_role_screen', [
                        'user_role_id' => $roleId,
                        'screen_id' => $screenId,
                        'can_read' => 1,
                        'can_create' => 1,
                        'can_update' => 1,
                        'can_delete' => 1,
                    ]);
                }
            }

            echo " ✓ Created data group & role permissions for $screenName\n";
        }

        // STEP 5: Clean up old menu items
        echo "\nStep 5: Cleaning up old menu items...\n";
        $oldMenuId = $this->conn->fetchOne("SELECT id FROM ohrm_menu_item WHERE menu_title = 'Payroll'");
        if ($oldMenuId) {
            $this->conn->delete('ohrm_menu_item', ['id' => $oldMenuId]);
            $this->conn->delete('ohrm_menu_item', ['parent_id' => $oldMenuId]);
            echo " ✓ Removed old menu items\n";
        }

        // STEP 6: Create menu items
        echo "\nStep 6: Creating Menu Items...\n";
        $icon = '{"icon":"money-check-alt"}';
        $this->conn->insert('ohrm_menu_item', [
            'menu_title' => 'Payroll',
            'screen_id' => null,
            'parent_id' => null,
            'level' => 1,
            'order_hint' => 80,
            'status' => 1,
            'additional_params' => $icon,
        ]);
        $parentMenuId = (int)$this->conn->lastInsertId();
        echo " ✓ Created main menu 'Payroll' (ID: $parentMenuId)\n";

        $menuItems = [
            ['title' => 'Dashboard', 'screen' => 'payroll_dashboard', 'order' => 10],
            ['title' => 'Employee Salary', 'screen' => 'payroll_employee_salary', 'order' => 20],
            ['title' => 'Payroll Periods', 'screen' => 'payroll_periods', 'order' => 30],
            ['title' => 'Reports', 'screen' => 'payroll_reports', 'order' => 40],
            ['title' => 'Configuration', 'screen' => 'payroll_configuration', 'order' => 50],
        ];

        foreach ($menuItems as $item) {
            $screenId = $screenIds[$item['screen']];
            $this->conn->insert('ohrm_menu_item', [
                'menu_title' => $item['title'],
                'screen_id' => $screenId,
                'parent_id' => $parentMenuId,
                'level' => 2,
                'order_hint' => $item['order'],
                'status' => 1,
            ]);
            echo " ✓ Created submenu '{$item['title']}'\n";
        }

        echo "\n✅ Payroll seeding completed successfully!\n";
    }

    /**
     * @inheritDoc
     */
    public
    function getVersion(): string
    {
        return '5.8.0';
    }
}