<?php
/**
 * OrangeHRM Payroll Plugin Installer
 *
 * Runs Payroll DB migration and seeds permissions/menu.
 * Compatible with OrangeHRM 5.7 / 5.8 standalone or cPanel installs.
 */

use OrangeHRM\Payroll\Migration\V5_7_0\Migration;
use Doctrine\DBAL\DriverManager;

require_once dirname(__FILE__) . '/../../../lib/confs/Conf.php';
require_once dirname(__FILE__) . '/../../../src/vendor/autoload.php';

try {
    echo "🔧 Starting Payroll Plugin installation...\n";

    // Load OrangeHRM DB configuration
    $conf = new Conf();
    $connectionParams = [
        'dbname' => $conf->getDbName(),
        'user' => $conf->getDbUser(),
        'password' => $conf->getDbPass(),
        'host' => $conf->getDbHost(),
        'driver' => 'pdo_mysql',
        'charset' => 'utf8mb4',
    ];

    // Create Doctrine connection
    $conn = DriverManager::getConnection($connectionParams);

    // Run the migration class
    $migration = new Migration($conn);
    $migration->up();

    echo "✅ Payroll Plugin installation completed successfully!\n";

} catch (Throwable $e) {
    echo "❌ Payroll Plugin installation failed: " . $e->getMessage() . "\n";
    exit(1);
}
