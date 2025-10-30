<?php

namespace OrangeHRM\Payroll\Controller;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\TransactionRequiredException;
use OrangeHRM\Core\Controller\AbstractVueController;
use OrangeHRM\Core\Controller\Exception\VueControllerException;
use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Core\Vue\Prop;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Payroll\Service\PayrollPeriodService;

class ViewPayrollPeriodController extends AbstractVueController
{
    protected ?PayrollPeriodService $service = null;

    protected function getPayrollPeriodService(): PayrollPeriodService
    {
        if (!$this->service instanceof PayrollPeriodService) {
            $this->service = new PayrollPeriodService();
        }
        return $this->service;
    }

    /**
     * @throws VueControllerException
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws TransactionRequiredException
     */
    public function preRender(Request $request): void
    {
        $pPid = $request->attributes->get('pPid');
        if ($pPid) {
            $component = new Component('view-payroll-period');
            $component->addProp(new Prop('pP-id', Prop::TYPE_NUMBER, $pPid));

            $details = $this->getPayrollPeriodService()->getPayrollPeriodDetails((int)$pPid);

            if ($details) {
                $component->addProp(new Prop('payroll-data', Prop::TYPE_OBJECT, $details));
            }

            $this->setComponent($component);
        } else {
            $this->handleBadRequest();
        }
    }
}
