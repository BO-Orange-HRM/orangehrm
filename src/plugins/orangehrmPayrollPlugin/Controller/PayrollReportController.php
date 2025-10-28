<?php

namespace OrangeHRM\Payroll\Controller;

use OrangeHRM\Core\Controller\AbstractVueController;
use OrangeHRM\Core\Service\ReportGeneratorService;
use OrangeHRM\Core\Vue\Component;
use OrangeHRM\Core\Vue\Prop;
use OrangeHRM\Framework\Http\Request;

class PayrollReportController extends AbstractVueController
{
    protected ?ReportGeneratorService $reportGeneratorService = null;
    protected function getReportGeneratorService(): ReportGeneratorService
    {
        if (!$this->reportGeneratorService instanceof ReportGeneratorService) {
            $this->reportGeneratorService = new ReportGeneratorService();
        }
        return $this->reportGeneratorService;
    }
    public function preRender(Request $request): void{
        if ($request->attributes->has('id')) {
            $id = $request->attributes->getInt('id');
            $reportName = $this->getReportGeneratorService()->getReportGeneratorDao()->getReportById($id)->getName();
            $component = new Component('payroll-report-view');
            $component->addProp(new Prop('report-id', Prop::TYPE_NUMBER, $id));
            $component->addProp(new Prop('report-name', Prop::TYPE_STRING, $reportName));

        }else{
            $component = new Component('payroll-report-list');
        }
        $this->setComponent($component);
    }

}