<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Core\Controller\AbstractVueController;
use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Framework\Http\Request;

class EmployeePayrollOverviewController extends AbstractVueController
{
    public function preRender(Request $request): void
    {
        $component = new Component('epp-payroll-overview');
        $this->setComponent($component);
    }

}