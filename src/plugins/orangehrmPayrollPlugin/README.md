# OrangeHRM Payroll Plugin — Technical Specification (Low-level)

> **Purpose:** Full, low-level specification of all models, controllers, templates, configs and supporting files required to build a custom **Payroll** plugin for OrangeHRM (Symfony / Doctrine style). This is a developer-facing document intended to be used as a checklist and implementation guide. Save as `orangehrm-payroll-plugin-spec.md` in your plugin repo root.

---

## Table of contents

1. [High-level overview](#high-level-overview)
2. [Architecture & integration points](#architecture--integration-points)
3. [Directory & file tree (complete)](#directory--file-tree-complete)
4. [Detailed file descriptions by folder](#detailed-file-descriptions-by-folder)

    * plugin manifest & registration
    * config
    * schema / sql / migrations
    * lib (services, dao, dto, entities)
    * modules (actions, templates, components)
    * controllers / actions
    * views / templates
    * assets (css/js)
    * tests
    * docs
5. [Database schema (DDL)](#database-schema-ddl)
6. [Business flows & use-cases mapped to files](#business-flows--use-cases-mapped-to-files)
7. [Security, permissions, RBAC integration](#security-permissions-rbac-integration)
8. [Packaging, installation & deployment steps](#packaging-installation--deployment-steps)
9. [Operational notes: logging, audits, backups](#operational-notes-logging-audits-backups)
10. [Appendix: sample pseudo-code snippets & templates](#appendix-sample-pseudo-code-snippets--templates)

---

## High-level overview

The plugin provides payroll functionality for both **monthly** and **hourly** employees by integrating with OrangeHRM's PIM and Time modules. Key capabilities:

* Define Payroll Components (earnings, deductions, hourly rates)
* Map components to employees (overrides)
* Read attendance/time entries to compute hourly pay
* Generate payslips (DB record + downloadable PDF)
* Export CSV for payment processing
* Admin UI for settings, rules, run payroll, and view payslips

Plugin targets OrangeHRM community edition (self-hosted) using Symfony/Doctrine conventions. All files are organized as a standard OrangeHRM plugin.

---

## Architecture & integration points

* **PIM** — for employee master data (employee_id, job, employment status, salary fields)
* **Time/Attendance** — for raw time punches; plugin will either query attendance tables or use provided Time API
* **Plugin registration** — plugin will register menu entries under Admin (or Payroll top level) and provide role-based access control
* **Database** — new payroll tables for components, mappings, payslips, payslip lines, and cached time summaries
* **PDF generation** — use DOMPDF or TCPDF library packaged via Composer to render HTML payslip templates to PDF
* **Export** — CSV export for batch bank uploads

---

## Directory & file tree (complete)

```
orangehrmPayrollPlugin/
├── plugin.yml
├── README.md
├── schema/
│   └── PayrollSchema.yml
├── sql/
│   └── migrations/
│       ├── 001_create_payroll_tables.sql
│       └── 002_seed_default_components.sql
├── config/
│   ├── settings.yml
│   └── routing.yml
├── lib/
│   ├── dao/
│   │   ├── PayrollDao.php
│   │   └── PayrollComponentDao.php
│   ├── service/
│   │   ├── PayrollService.php
│   │   ├── PayslipService.php
│   │   └── PayrollExportService.php
│   ├── model/
│   │   ├── PayrollModel.php
│   │   └── PayrollComponentModel.php
│   ├── dto/
│   │   ├── PayrollComponentDTO.php
│   │   └── PayslipDTO.php
│   ├── entity/
│   │   ├── PayrollEntity.php
│   │   ├── PayrollComponentEntity.php
│   │   └── PayslipEntity.php
│   └── util/
│       ├── TaxCalculator.php
│       └── PdfGenerator.php
├── modules/
│   └── payroll/
│       ├── actions/
│       │   ├── indexAction.class.php
│       │   ├── generateAction.class.php
│       │   ├── viewPayslipAction.class.php
│       │   ├── componentsAction.class.php
│       │   └── settingsAction.class.php
│       ├── templates/
│       │   ├── indexSuccess.php
│       │   ├── generateSuccess.php
│       │   ├── viewPayslipSuccess.php
│       │   ├── componentsSuccess.php
│       │   └── settingsSuccess.php
│       └── lib/
│           └── forms/
│               ├── PayrollGenerateForm.php
│               └── PayrollComponentForm.php
├── web/
│   ├── css/
│   │   └── payroll.css
│   └── js/
│       └── payroll.js
├── tests/
│   ├── unit/
│   │   ├── PayrollServiceTest.php
│   │   └── PayrollDaoTest.php
│   └── integration/
│       └── PayslipGenerationIntegrationTest.php
└── assets/
    └── payslip_template.html.twig
```

---

## Detailed file descriptions by folder

> Each file below includes: file path, purpose, what it contains, and implementation notes.

### Top-level files

#### `plugin.yml`

* **Purpose:** Plugin manifest/metadata used by OrangeHRM to discover and enable the plugin.
* **Contains:** YAML fields: `name`, `version`, `author`, `description`, `dependencies`, `autoload` entries (if any), and an `enabled: true` flag for development.
* **Implementation notes:** Keep version aligned with releases. Example contents:

```yaml
plugin:
  name: orangehrmPayrollPlugin
  version: 1.0.0
  author: "Your Name"
  description: "Payroll module for calculating monthly and hourly payslips"
  dependencies:
    - orangehrmCorePlugin
  enabled: true
```

#### `README.md`

* **Purpose:** Developer instructions, how to install locally, run migrations, run tests.
* **Contains:** Installation steps, environment variables, sample config and notes about required PHP extensions (gd, mbstring), and Composer dependencies (dompdf/dompdf).

---

### `schema/PayrollSchema.yml`

* **Purpose:** Doctrine schema declaration for plugin entities to be generated by `doctrine:build`.
* **Contains:** ORM table definitions for `Payroll`, `PayrollComponent`, `PayrollEmployeeComponent`, `Payslip`, `PayslipLine`, `PayslipExportLog`, `TimeEntryCache`.
* **Implementation notes:** Include foreign keys to `hs_hr_employee` or equivalent OrangeHRM employee table. Use `created_at` timestamps.

---

### `sql/migrations/001_create_payroll_tables.sql`

* **Purpose:** Raw DDL to create the plugin database structure; used for deployments in environments where `doctrine:build` is not desired.
* **Contains:** CREATE TABLE statements for all payroll tables with indexes and FK constraints. Should be idempotent (use `IF NOT EXISTS`).
* **Implementation notes:** Provide `002_seed_default_components.sql` to create `Basic`, `Housing`, `Transport`, `HourlyRate` default components.

---

### `config/settings.yml`

* **Purpose:** Plugin configuration values (pay period default, rounding rules, tax thresholds placeholder, PDF template path).
* **Contains:** YAML keys such as `pay_period: monthly`, `default_currency: BWP`, `pdf_template: assets/payslip_template.html.twig`.
* **Implementation notes:** Support environment overrides via `.env` or OrangeHRM config merge.

#### `config/routing.yml`

* **Purpose:** Map HTTP routes to controller actions for the plugin.
* **Contains:** Route definitions like `/payroll`, `/payroll/generate`, `/payroll/view/:id`, `/payroll/components`, and their corresponding module/action.

Example:

```yaml
payroll_index:
  url: /payroll
  param: { module: payroll, action: index }

payroll_generate:
  url: /payroll/generate
  param: { module: payroll, action: generate }
```

---

### `lib/` — core server-side code

This is the core logic split into layers: DAO (data access), Service (business logic), Model/Entity (ORM), DTO (data transfer), Util (helpers).

#### `lib/dao/PayrollDao.php`

* **Purpose:** Central data access layer for payslips, components, mappings.
* **Contains:** Methods:

    * `getComponentById($id)`
    * `getComponents()`
    * `saveComponent(PayrollComponentEntity $comp)`
    * `getEmployeeComponents($employeeId)`
    * `savePayslip(PayslipEntity $payslip)`
    * `getPayslip($payslipId)`
    * `listPayslips($criteria = [])`
    * `cacheTimeEntries($employeeId, $startDate, $endDate, $hours)`
* **Implementation notes:** Use Doctrine ORM for entities or raw DBAL if preferred. Always use parameterized queries.

#### `lib/dao/PayrollComponentDao.php`

* **Purpose:** Component-specific queries (CRUD, search, default components loader).
* **Contains:** `findByName()`, `findDefaultEarnings()`, etc.

#### `lib/service/PayrollService.php`

* **Purpose:** Business logic service orchestrating payroll calculations.
* **Contains:** Methods:

    * `generatePayslip($employeeId, $startDate, $endDate, $createdBy)` — main entrypoint
    * `calculateEarnings($employeeId, $period)` — returns array of component -> amount
    * `calculateDeductions($employeeId, $grossAmount)` — tax and statutory, return breakdown
    * `hourlyHoursForPeriod($employeeId, $startDate, $endDate)` — reads attendance or cached time entries
    * `applyEmployeeOverrides($employeeId, $componentList)`
* **Implementation notes:** Stateless; uses DAOs. Keep pure functions for calculation to ease unit testing.

#### `lib/service/PayslipService.php`

* **Purpose:** Persistence and PDF/export logic for payslips.
* **Contains:** Methods:

    * `savePayslip($payslipDto)`
    * `renderPayslipPdf($payslipId, $options = [])` — uses `PdfGenerator`
    * `exportPayslipCsv($payslipId)`
* **Implementation notes:** Keep rendering separate from data calculation.

#### `lib/service/PayrollExportService.php`

* **Purpose:** Export batch CSV for bank processing (columns: employee_id, bank_account, net_amount, reference).
* **Contains:** `exportBatchForDateRange($start, $end, $filters = [])`.

#### `lib/model/PayrollModel.php` & `PayrollComponentModel.php`

* **Purpose:** Domain models used by UI forms and template helpers.
* **Contains:** Lightweight wrappers to map entities to arrays consumed by templates.

#### `lib/dto/PayrollComponentDTO.php` & `PayslipDTO.php`

* **Purpose:** Immutable value objects used to pass data between layers and to templates.
* **Contains:** public getters, simple validation.

#### `lib/entity/*.php` (if not using YAML schema)

* **Purpose:** Doctrine entity classes representing table rows.
* **Contains:** Fields with annotations: `@Entity`, `@Table(name=...)`, field definitions.

#### `lib/util/TaxCalculator.php`

* **Purpose:** Encapsulate statutory tax, NSSF, UIF or local equivalents. This file will implement the country-specific tax logic and configurable thresholds.
* **Contains:** `calculateTax($gross, $employeeId, $period)` returns breakdown array and total.

#### `lib/util/PdfGenerator.php`

* **Purpose:** Wrapper around DOMPDF/TCPDF that: 1) renders HTML template with data; 2) returns PDF bytes or writes to disk.
* **Contains:** `renderToPdf($html, $options = [])`, `savePdfToPath($pdfBytes, $path)`.

---

### `modules/payroll/` — controllers (actions) and forms

> OrangeHRM/Symfony-style actions are contained here. Each action class corresponds to a route.

#### `modules/payroll/actions/indexAction.class.php`

* **Purpose:** Dashboard for payroll: quick stats, run last payroll, recent payslips.
* **Contains:** `execute($request)` — loads summary data using `PayrollService` and `PayrollDao`.
* **Template:** `indexSuccess.php` shows recent payslips, links to generate, components, settings.

#### `modules/payroll/actions/generateAction.class.php`

* **Purpose:** Handle payroll generation UI & form submission (single or batch).
* **Contains:** `execute($request)` — if GET show `PayrollGenerateForm`, if POST validate input (start/end/employees) and call `PayrollService::generatePayslip` per employee. Handles job queueing if long-running.
* **Template:** `generateSuccess.php` displays results and download links.

#### `modules/payroll/actions/viewPayslipAction.class.php`

* **Purpose:** View single payslip details; provide PDF download and CSV export endpoints.
* **Contains:** `execute()` loads payslip by id, ensures permission, returns data to template `viewPayslipSuccess.php`.

#### `modules/payroll/actions/componentsAction.class.php`

* **Purpose:** CRUD for payroll components (earnings/deductions/rates).
* **Contains:** list, add, edit, delete flows. Use `PayrollComponentForm` for add/edit.
* **Templates:** `componentsSuccess.php`, `componentForm.php` (partial form template).

#### `modules/payroll/actions/settingsAction.class.php`

* **Purpose:** Payroll configuration: default pay period, rounding rules, tax table upload.
* **Contains:** form handling and persists settings to `config/settings.yml` or OrangeHRM config system.

#### `modules/payroll/lib/forms/PayrollGenerateForm.php`

* **Purpose:** Validate input for payroll generation (dates, employee list, generate mode single/batch).
* **Contains:** field definitions, validators, CSRF protection.

#### `modules/payroll/lib/forms/PayrollComponentForm.php`

* **Purpose:** Validate component creation/editing inputs: name, type, calc method, default value.

---

### `modules/payroll/templates/` — UI templates

All templates should follow OrangeHRM markup and UI conventions. Prefer minimal inline logic; compute values in action classes and pass to template.

#### `indexSuccess.php`

* **Contains:** Dashboard widgets: Recent payslips table (date, employee, gross, net), Quick Actions: Generate Payroll, Manage Components, Settings.

#### `generateSuccess.php`

* **Contains:** Form to select period (start/end) or predefined (month), multi-select employees, checkboxes for include/exclude types, progress output after POST. Shows summary results with links to generated payslips.

#### `viewPayslipSuccess.php`

* **Contains:** Full payslip breakdown (earnings lines, deduction lines), gross, deductions, net, PDF download link, CSV/export button.

#### `componentsSuccess.php`

* **Contains:** List of components (name, type, calculation, default value), edit/delete buttons, add new component button.

#### `settingsSuccess.php`

* **Contains:** forms for pay period, PDF template selection, rounding, tax table upload.

---

### `web/css/payroll.css` and `web/js/payroll.js`

* **Purpose:** Small assets for the plugin UI for styling and client-side interactions (date pickers, async generate progress updates).
* **Contains:** CSS classes matching OrangeHRM theme; JS to call `/payroll/generate` via AJAX and poll status or show progress.

---

### `assets/payslip_template.html.twig`

* **Purpose:** HTML payslip template used by `PdfGenerator`.
* **Contains:** Template placeholders for company info, employee details, payslip lines loop, totals, footer, and basic inline CSS. Provide separate sections for earnings/deductions and a QR code placeholder for verification if needed.

---

### `tests/` — automated tests

* `tests/unit/PayrollServiceTest.php` — unit tests for gross/deduction calculations using known inputs.
* `tests/unit/PayrollDaoTest.php` — unit tests for DAO CRUD behavior (use in-memory sqlite or test DB).
* `tests/integration/PayslipGenerationIntegrationTest.php` — create a test employee, insert attendance rows, run generatePayslip, assert DB rows and PDF creation.

---

## Database schema (DDL)

> Full DDL (abbreviated). Put the full SQL in `sql/migrations/001_create_payroll_tables.sql`.

```sql
-- payroll_component
CREATE TABLE IF NOT EXISTS payroll_component (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  code VARCHAR(64) UNIQUE,
  type ENUM('earning','deduction','rate') NOT NULL,
  calculation ENUM('fixed','percentage','hourly') NOT NULL,
  value DECIMAL(12,2) DEFAULT 0,
  is_default TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- payroll_employee_component (overrides)
CREATE TABLE IF NOT EXISTS payroll_employee_component (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  component_id INT NOT NULL,
  value DECIMAL(12,2) NOT NULL,
  UNIQUE KEY ux_emp_comp (employee_id, component_id),
  FOREIGN KEY (component_id) REFERENCES payroll_component(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- payroll_payslip
CREATE TABLE IF NOT EXISTS payroll_payslip (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  gross_amount DECIMAL(12,2) DEFAULT 0,
  total_deductions DECIMAL(12,2) DEFAULT 0,
  net_amount DECIMAL(12,2) DEFAULT 0,
  pdf_path VARCHAR(255),
  generated_by INT,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- payroll_payslip_line
CREATE TABLE IF NOT EXISTS payroll_payslip_line (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payslip_id INT NOT NULL,
  component_id INT NOT NULL,
  label VARCHAR(128),
  amount DECIMAL(12,2) DEFAULT 0,
  FOREIGN KEY (payslip_id) REFERENCES payroll_payslip(id) ON DELETE CASCADE,
  FOREIGN KEY (component_id) REFERENCES payroll_component(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- payroll_time_entry_cache
CREATE TABLE IF NOT EXISTS payroll_time_entry_cache (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  entry_date DATE NOT NULL,
  hours DECIMAL(6,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (employee_id, entry_date)
) ENGINE=InnoDB;
```

Notes:

* `employee_id` should map to OrangeHRM employee table `hs_hr_employee` or schema in your version. Document exact table and field mapping in README.

---

## Business flows & use-cases mapped to files

1. **Add Payroll Component**

    * UI: `modules/payroll/templates/componentsSuccess.php`
    * Form: `modules/payroll/lib/forms/PayrollComponentForm.php`
    * Action: `modules/payroll/actions/componentsAction.class.php`
    * Persistence: `lib/dao/PayrollComponentDao.php`

2. **Assign Component to Employee (override)**

    * UI: Employee Salary tab extension (hook) or a dedicated mapping page
    * Action: `componentsAction` or an `employeeMappingAction` (optional)
    * Persistence: `lib/dao/PayrollDao.php` -> `payroll_employee_component`

3. **Record Attendance (existing OrangeHRM)**

    * Use `Time` module. Optionally run a cron job to cache daily totals into `payroll_time_entry_cache` via `lib/service/PayrollService::cacheTimeEntries`

4. **Generate Payslip**

    * UI: `modules/payroll/actions/generateAction` (form + POST)
    * Calculation: `lib/service/PayrollService::generatePayslip`
    * Save: `lib/dao/PayrollDao::savePayslip`
    * Render PDF: `lib/service/PayslipService::renderPayslipPdf`

5. **View/Download Payslip**

    * Route: `/payroll/view/:id`
    * Action: `viewPayslipAction` -> uses `PayrollDao` and `PayslipService` to return PDF path or generate on-demand

6. **Export Batch CSV**

    * UI: `indexSuccess.php` or `generateSuccess.php` button
    * Service: `PayrollExportService::exportBatchForDateRange`

---

## Security, permissions, RBAC integration

* Add new privileges/roles entries (if OrangeHRM uses `auth` or `admin` ACL tables) for:

    * `payroll_view`
    * `payroll_generate`
    * `payroll_manage_components`
    * `payroll_manage_settings`
* Protect controller actions by checking `user->hasCredential('payroll_generate')` etc.
* Sanitize all inputs and protect endpoints with CSRF tokens for forms.
* Only allow PDF/CSV downloads to users with `payroll_view`.

---

## Packaging, installation & deployment steps

1. **Local development**

    * Place plugin folder under `symfony/plugins/orangehrmPayrollPlugin` (or `plugins/` path used by your OrangeHRM version)
    * Symlink during dev for convenience: `ln -s ~/dev/Payroll-Plugin ~/Projects/OrangeHRM/symfony/plugins/orangehrmPayrollPlugin`
2. **Run schema migration**

    * Option A (Doctrine): `php symfony doctrine:build --all --no-confirmation`
    * Option B (SQL): run `sql/migrations/001_create_payroll_tables.sql` against DB
3. **Enable plugin**

    * Ensure `plugin.yml` has `enabled: true` and restart the web server or clear cache: `php symfony cc` or `rm -rf cache/*`
4. **Composer deps**

    * Add `dompdf/dompdf` to plugin `composer.json` or to root composer then run `composer install`
5. **Permissions**

    * Ensure web server user owns cache and plugin files as needed: `chown -R www-data:www-data symfony/plugins/orangehrmPayrollPlugin`
6. **Testing**

    * Run unit tests `phpunit tests/unit` (configure phpunit.xml)
7. **Deploy to server**

    * Zip plugin folder: `zip -r orangehrm-payroll-plugin-v1.0.0.zip orangehrmPayrollPlugin`
    * SCP to server, unzip into OrangeHRM plugins folder, run DB migration, clear cache, restart PHP-FPM

---

## Operational notes: logging, audits, backups

* Log payroll generation events to a `payroll_event_log` table with user id, timestamp, operation, and payload (JSON) for audit.
* Keep generated PDFs in a directory structure `uploads/payslips/{YYYY}/{MM}/{employee_id}/` with read permissions restricted.
* Schedule DB backups. Payroll data is sensitive.

---

## Appendix: sample pseudo-code snippets & templates

### 1) `PayrollService::generatePayslip()` (high-level pseudo)

```php
public function generatePayslip($employeeId, $startDate, $endDate, $createdBy) {
    // 1. Load employee & employment status via PIM
    $employee = $this->employeeService->getEmployee($employeeId);

    // 2. Load component definitions and employee overrides
    $components = $this->componentDao->getComponentsForEmployee($employeeId);

    // 3. For hourly employees fetch hours
    if ($employee->isHourly()) {
        $hours = $this->hourlyHoursForPeriod($employeeId, $startDate, $endDate);
    }

    // 4. Calculate lines
    $lines = [];
    foreach ($components as $c) {
        $amount = 0;
        switch ($c->calculation) {
            case 'fixed': $amount = $c->value; break;
            case 'percentage': $amount = ($c->value / 100) * $base; break;
            case 'hourly': $amount = $hours * $c->value; break;
        }
        $lines[] = ['component' => $c, 'amount' => $amount];
        $gross += $amount;
    }

    // 5. Deductions
    $deductions = $this->taxCalculator->calculate($gross, $employeeId);

    // 6. Persist payslip and lines
    $payslipId = $this->dao->savePayslip(...);

    // 7. Render PDF
    $html = $this->templating->render('assets/payslip_template.html.twig', compact('employee','lines','deductions'));
    $pdfPath = $this->pdfGenerator->renderToFile($html, '/uploads/payslips/...');

    return $payslipId;
}
```

### 2) Example `payslip_template.html.twig` placeholders

```twig
<html>
  <body>
    <h1>{{ company.name }}</h1>
    <h2>Payslip: {{ payslip.period_start|date('Y-m-d') }} - {{ payslip.period_end|date('Y-m-d') }}</h2>
    <p>Employee: {{ employee.fullName }} ({{ employee.employeeNumber }})</p>

    <h3>Earnings</h3>
    <table>
      <thead><tr><th>Component</th><th>Amount</th></tr></thead>
      <tbody>
        {% for line in payslip.earnings %}
          <tr><td>{{ line.component.name }}</td><td>{{ line.amount }}</td></tr>
        {% endfor %}
      </tbody>
    </table>

    <h3>Deductions</h3>
    <!-- similar loop -->

    <h2>Net Pay: {{ payslip.net_amount }}</h2>

  </body>
</html>
```

---

## Final checklist (before first production deploy)

* [ ] Schema migration tested in staging
* [ ] Role/permissions created and verified
* [ ] Unit & integration tests passing
* [ ] PDF generation and storage tested
* [ ] CSV export format verified with payroll bank
* [ ] Backup & restore tested for payroll tables
* [ ] Audit logging enabled

---

If you want, I can now:

* generate the actual plugin skeleton files (empty class files, manifest, schema and SQL) zipped and ready to drop into your local OrangeHRM project; or
* produce concrete code for the most critical files (`PayrollService`, `PayrollDao`, `PayrollGenerateForm`, `payslip_template`) so you can start implementing immediately.

Tell me which next step you want and I will produce the files.
