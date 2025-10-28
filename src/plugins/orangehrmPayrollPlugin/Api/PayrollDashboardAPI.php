<?php

namespace OrangeHRM\Payroll\Api;

use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\ForbiddenException;
use OrangeHRM\Core\Api\V2\Exception\NotImplementedException;
use OrangeHRM\Core\Api\V2\Exception\RecordNotFoundException;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Serializer\NormalizeException;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Payroll\Api\Model\PayrollDashboardModel;
use OrangeHRM\Payroll\Service\PayrollPeriodService;
use OrangeHRM\Payroll\Service\Traits\PayrollPeriodServiceTrait;
use Symfony\Component\HttpFoundation\JsonResponse;

class PayrollDashboardAPI extends Endpoint implements ResourceEndpoint
{
    use PayrollPeriodServiceTrait;

    public function __construct()
    {
    }


    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $service = $this->getPayrollPeriodService();

        // Get active payroll period
        $activePeriod = $service->getActivePeriod();

        // Get KPIs
        $kpis = [
            'totalEmployees' => $service->getTotalEmployeesInActivePeriod(),
            'totalAmount' => $service->getTotalAmountForActivePeriod(),
            'pendingApprovals' => $service->getPendingApprovalsCount(),
            'lastRunDate' => $service->getLastPayrollRunDate()
        ];

        // Get alerts
        $alerts = $service->getPayrollAlerts();

        // Get recent periods
        $recentPeriods = $service->getRecentPeriods(5);

        $data = [
            'activePeriod' => $activePeriod ? $this->formatPeriod($activePeriod) : null,
            'kpis' => $kpis,
            'alerts' => $alerts,
            'recentPeriods' => array_map([$this, 'formatPeriodForTable'], $recentPeriods)
        ];

        return new EndpointResourceResult($this->getModelClass(), $data);
    }

    /**
     * @throws NormalizeException
     * @throws NotImplementedException
     * @throws ForbiddenException
     * @throws RecordNotFoundException
     */
    public function getAll(): EndpointResult
    {
        $service = $this->getPayrollPeriodService();

        // Get active payroll period
        $activePeriod = $service->getActivePeriod();

        // Get KPIs
        $kpis = [
            'totalEmployees' => $service->getTotalEmployeesInActivePeriod(),
            'totalAmount' => $service->getTotalAmountForActivePeriod(),
            'pendingApprovals' => $service->getPendingApprovalsCount(),
            'lastRunDate' => $service->getLastPayrollRunDate()
        ];

        // Get alerts
        $alerts = $service->getPayrollAlerts();

        // Get recent periods
        $recentPeriods = $service->getRecentPeriods(5);

        $data = [
            'activePeriod' => $activePeriod ? $this->formatPeriod($activePeriod) : null,
            'kpis' => $kpis,
            'alerts' => $alerts,
            'recentPeriods' => array_map([$this, 'formatPeriodForTable'], $recentPeriods)
        ];

        // Wrap the array in a collection result
        return new EndpointResourceResult($this->getModelClass(), [$data]);
    }

    public function getOneResponse(): JsonResponse
    {
        $service = $this->getPayrollPeriodService();

        $activePeriod = $service->getActivePeriod();
        $kpis = [
            'totalEmployees' => $service->getTotalEmployeesInActivePeriod(),
            'totalAmount' => $service->getTotalAmountForActivePeriod(),
            'pendingApprovals' => $service->getPendingApprovalsCount(),
            'lastRunDate' => $service->getLastPayrollRunDate()
        ];

        $alerts = $service->getPayrollAlerts();
        $recentPeriods = $service->getRecentPeriods(5);

        $data = [
            'activePeriod' => $activePeriod ? $this->formatPeriod($activePeriod) : null,
            'kpis' => $kpis,
            'alerts' => $alerts,
            'recentPeriods' => array_map([$this, 'formatPeriodForTable'], $recentPeriods)
        ];

        return new JsonResponse($data);
    }

    public function getAllResponse(): JsonResponse
    {
        return $this->getOneResponse(); // or wrap in an array if you want a collection
    }


    /**
     * @param object $period
     * @return array
     */
    private function formatPeriod(object $period): array
    {
        return [
            'id' => $period->getId(),
            'start_date' => $period->getStartDate()->format('Y-m-d'),
            'end_date' => $period->getEndDate()->format('Y-m-d'),
            'status' => $period->getStatus()
        ];
    }

    /**
     * @param object $period
     * @return array
     */
    private function formatPeriodForTable(object $period): array
    {
        return [
            'id' => $period->getId(),
            'period' => $period->getStartDate()->format('Y-m-d') . ' - ' . $period->getEndDate()->format('Y-m-d'),
            'status' => $period->getStatus(),
            'totalAmount' => $period->getTotalAmount()
        ];
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResourceResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResourceResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    private function getModelClass(): string
    {
        return PayrollDashboardModel::class;
    }
}