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
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Serializer\NormalizeException;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Payroll\Api\Model\PayrollPeriodModel;
use OrangeHRM\Payroll\Service\PayrollPeriodService;

/**
 * @api {get} /api/v2/payroll/periods List Payroll Periods
 * @api {get} /api/v2/payroll/periods/:id Get Payroll Period
 * @api {post} /api/v2/payroll/periods Create Payroll Period
 * @api {put} /api/v2/payroll/periods/:id Update Payroll Period
 * @api {delete} /api/v2/payroll/periods/:id Delete Payroll Period
 */
class PayrollPeriodAPI extends Endpoint implements CrudEndpoint
{
    private ?PayrollPeriodService $service = null;

    public const PAYROLL_PERIOD_ID = 'id';

    /**
     * GET /api/v2/payroll/periods
     * Retrieve all payroll periods
     * @throws NormalizeException
     * @throws NotSupported
     */
    public function getAll(): EndpointResult
    {
        $periods = $this->getService()->getAll();

        return new EndpointCollectionResult($this->getModelClass(), $periods);
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

        return new EndpointResourceResult($this->getModelClass(), $period);
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
            return new EndpointResourceResult($this->getModelClass(), $period);

        } catch (NormalizeException|Exception $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
    }

    /**
     * PUT /api/v2/payroll/periods/{id}
     * Update an existing payroll period
     */
    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PAYROLL_PERIOD_ID
        );
        $params = $this->getRequest()->getBody();

        try {
            $period = $this->getService()->getPayrollPeriodDao()->findById($id);
            if (!$period) {
                throw $this->getRecordNotFoundException("Payroll period not found (ID: $id)");
            }

            if (isset($params->all()['start_date'])) {
                $period->setStartDate(new \DateTime($params->all()['start_date']));
            }
            if (isset($params->all()['end_date'])) {
                $period->setEndDate(new \DateTime($params->all()['end_date']));
            }
            if (isset($params->all()['status'])) {
                $period->setStatus($params->all()['status']);
            }

            $period->setProcessedAt(new \DateTime());

            $updated = $this->getService()->savePayrollPeriod($period);

            return new EndpointResourceResult($this->getModelClass(), $updated);
        } catch (Exception $e) {
            throw $this->getBadRequestException($e->getMessage());
        }
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PAYROLL_PERIOD_ID,
                    new Rule(Rules::POSITIVE)
                )
            ),
            new ParamRule('start_date', new Rule(Rules::DATE)),
            new ParamRule('end_date', new Rule(Rules::DATE)),
            new ParamRule('status', new Rule(Rules::STRING_TYPE)),
        );
    }

    /**
     * DELETE /api/v2/payroll/periods/{id}
     * Delete a payroll period
     * @throws BadRequestException
     */
    public function delete(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PAYROLL_PERIOD_ID
        );

        try {
            $period = $this->getService()->getPayrollPeriodDao()->findById($id);
            if (!$period) {
                throw $this->getRecordNotFoundException("Payroll period not found (ID: $id)");
            }

            $this->getService()->getPayrollPeriodDao()->delete($period);

            return new EndpointCollectionResult(ArrayModel::class, [
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
        return PayrollPeriodModel::class;
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
