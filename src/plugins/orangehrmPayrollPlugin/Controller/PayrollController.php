<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Core\Controller\AbstractModuleController;
use OrangeHRM\Framework\Http\RedirectResponse;

class PayrollController extends AbstractModuleController
{
    public function handle(): RedirectResponse
    {
        return $this->redirect('/payroll/viewPayrollDashboard');
    }
}