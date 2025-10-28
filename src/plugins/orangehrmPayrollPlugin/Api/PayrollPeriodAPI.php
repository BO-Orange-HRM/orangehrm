<?php

namespace OrangeHRM\Payroll\Api;

use Doctrine\ORM\Exception\NotSupported;
use Exception;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Exception\RecordNotFoundException;
use OrangeHRM\Core\Api\V2\Serializer\NormalizeException;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\PayrollPeriod;
use OrangeHRM\Payroll\Service\PayrollPeriodService;

/**
 * @api {get} /api/v2/payroll/periods List Payroll Periods
 * @api {get} /api/v2/payroll/periods/:id Get Payroll Period
 * @api {post} /api/v2/payroll/periods Create Payroll Period
 * @api {put} /api/v2/payroll/periods/:id Update Payroll Period
 * @api {delete} /api/v2/payroll/periods/:id Delete Payroll Period
 */
class PayrollPeriodAPI extends Endpoint  implements CrudEndpoint
{
    private ?PayrollPeriodService $service = null;

    /**
     * GET /api/v2/payroll/periods
     * Retrieve all payroll periods
     * @throws NormalizeException
     * @throws NotSupported
     */
    public function getAll(): EndpointResult
    {
        $periods = $this->getService()->getAll();

        // Normalize entity objects to arrays
        $data = array_map(function (PayrollPeriod $p) {
            return [
                'id' => $p->getId(),
                'startDate' => $p->getStartDate()->format('Y-m-d'),
                'endDate' => $p->getEndDate()->format('Y-m-d'),
                'status' => $p->getStatus(),
                'frequency' => $p->getFrequency(),
                'totalAmount' => $p->getTotalAmount(),
                'createdAt' => $p->getCreatedAt()->format('Y-m-d H:i:s'),
                'processedAt' => $p->getProcessedAt()->format('Y-m-d H:i:s'),
            ];
        }, $periods);

        return new EndpointCollectionResult($this->getModelClass(),$data);
    }

    /**
     * GET /api/v2/payroll/periods/{id}
     * Retrieve a single payroll period
     * @throws NormalizeException
     * @throws InvalidParamException
     * @throws RecordNotFoundException
     */
    public function getOne(): EndpointResult
    {
        $params = $this->getRequestParams();

        if (!$params->has('id')) {
            throw $this->getRecordNotFoundException('Missing required parameter: id');
        }

        $id = $params->getInt('id');
        $period = $this->getService()->getPayrollPeriodDao()->findById((int)$id);

        if (!$period) {
            throw $this->getRecordNotFoundException("Payroll period not found (ID: $id)");
        }

        return new EndpointResourceResult($this->getModelClass(),[
            'id' => $period->getId(),
            'startDate' => $period->getStartDate()->format('Y-m-d'),
            'endDate' => $period->getEndDate()->format('Y-m-d'),
            'status' => $period->getStatus(),
            'frequency' => $period->getFrequency(),
            'totalAmount' => $period->getTotalAmount(),
            'createdAt' => $period->getCreatedAt()->format('Y-m-d H:i:s'),
            'processedAt' => $period->getProcessedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule('id', new Rule(Rules::INT_TYPE), new Rule(Rules::REQUIRED))
        );
    }

    /**
     * POST /api/v2/payroll/periods
     * Create a new payroll period
     * @throws BadRequestException
     */
    public function create(): EndpointResult
    {
        $params = $this->getRequest()->getBody();
        try {
            $period = $this->getService()->createPeriod($params->all());
            return new EndpointResourceResult($this->getModelClass(), [
                'message' => 'Payroll period created successfully',
                'data' => [
                    'id' => $period->getId(),
                    'startDate' => $period->getStartDate()->format('Y-m-d'),
                    'endDate' => $period->getEndDate()->format('Y-m-d'),
                    'paymentDate' => $period->getProcessedAt()->format('Y-m-d'),
                    'frequency' => $period->getFrequency(),
                ]
            ]);

        } catch (NormalizeException | Exception $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
    }

    /**
     * PUT /api/v2/payroll/periods/{id}
     * Update an existing payroll period
     */
    public function update(): EndpointResult
    {
        $id = (int)$this->getRequest()->getUrlParam('id');
        $params = $this->getRequest()->getBodyParams();

        try {
            $period = $this->getService()->getPayrollPeriodDao()->findById($id);
            if (!$period) {
                throw $this->getRecordNotFoundException("Payroll period not found (ID: $id)");
            }

            if (isset($params['startDate'])) {
                $period->setStartDate(new \DateTime($params['startDate']));
            }
            if (isset($params['endDate'])) {
                $period->setEndDate(new \DateTime($params['endDate']));
            }
            if (isset($params['status'])) {
                $period->setStatus($params['status']);
            }
            if (isset($params['frequency'])) {
                $period->setFrequency($params['frequency']);
            }

            $updated = $this->getService()->savePayrollPeriod($period);

            return new EndpointResourceResult($this->getModelClass(),[
                'message' => 'Payroll period updated successfully',
                'id' => $updated->getId(),
            ]);
        } catch (Exception $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule('id', new Rule(Rules::INT_TYPE), new Rule(Rules::REQUIRED))
        );
    }

    /**
     * DELETE /api/v2/payroll/periods/{id}
     * Delete a payroll period
     * @throws BadRequestException
     */
    public function delete(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt('id');

        try {
            $period = $this->getService()->getPayrollPeriodDao()->findById($id);
            if (!$period) {
                throw $this->getRecordNotFoundException("Payroll period not found (ID: $id)");
            }

            $this->getService()->getPayrollPeriodDao()->delete($period);

            return new EndpointCollectionResult($this->getModelClass(),[
                'success' => true,
                'message' => "Payroll period (ID: $id) deleted successfully",
            ]);
        } catch (Exception $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule('id', new Rule(Rules::INT_TYPE), new Rule(Rules::REQUIRED))
        );
    }

    private function getModelClass()
    {
        return PayrollPeriod::class;
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        // TODO: Implement getValidationRuleForGetAll() method.
        return new ParamRuleCollection();
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule('startDate', new Rule(Rules::REQUIRED), new Rule(Rules::DATE)),
            new ParamRule('endDate', new Rule(Rules::REQUIRED), new Rule(Rules::DATE)),
            new ParamRule('paymentDate', new Rule(Rules::REQUIRED), new Rule(Rules::DATE)),
            new ParamRule('frequency', new Rule(Rules::REQUIRED), new Rule(Rules::STRING_TYPE))
        );
    }

    public function getService(): PayrollPeriodService
    {
        if (!$this->service) {
            $this->service = new PayrollPeriodService();
        }
        return $this->service;
    }
}
