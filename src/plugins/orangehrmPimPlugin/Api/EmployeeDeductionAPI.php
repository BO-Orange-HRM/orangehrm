<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 */

namespace OrangeHRM\Pim\Api;

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\EmployeeDeduction;
use OrangeHRM\Pim\Api\Model\EmployeeDeductionModel;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;

class EmployeeDeductionAPI extends Endpoint implements CrudEndpoint
{
    use EmployeeServiceTrait;

    public const PARAMETER_NAME = 'name';
    public const PARAMETER_AMOUNT = 'amount';
    public const PARAMETER_COMMENT = 'comment';
    public const PARAMETER_EFFECTIVE_DATE = 'effectiveDate';

    public const PARAM_RULE_NAME_MAX_LENGTH = 100;
    public const PARAM_RULE_AMOUNT_MAX = 999999999.99;
    public const PARAM_RULE_AMOUNT_MIN = 0;

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResourceResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        $deductionId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );

        $deduction = $this->getEmployeeService()
            ->getEmployeeDeductionDao()
            ->getEmployeeDeduction($empNumber, $deductionId);

        if (!$deduction instanceof EmployeeDeduction) {
            throw $this->getInvalidArgumentException('Deduction not found');
        }

        return new EndpointResourceResult(EmployeeDeductionModel::class, $deduction);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)),
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE))
        );
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointCollectionResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );

        $deductions = $this->getEmployeeService()
            ->getEmployeeDeductionDao()
            ->getEmployeeDeductions($empNumber);

        return new EndpointCollectionResult(
            EmployeeDeductionModel::class,
            $deductions,
            new ParameterBag([CommonParams::PARAM_EMP_NUMBER => $empNumber])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS))
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResourceResult
    {
        $deduction = new EmployeeDeduction();
        $this->setDeductionParams($deduction);

        $deduction = $this->getEmployeeService()
            ->getEmployeeDeductionDao()
            ->saveEmployeeDeduction($deduction);

        return new EndpointResourceResult(EmployeeDeductionModel::class, $deduction);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)),
            ...$this->getCommonBodyValidationRules(),
        );
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResourceResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        $deductionId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_ID
        );

        $deduction = $this->getEmployeeService()
            ->getEmployeeDeductionDao()
            ->getEmployeeDeduction($empNumber, $deductionId);

        if (!$deduction instanceof EmployeeDeduction) {
            throw $this->getInvalidArgumentException('Deduction not found');
        }

        $this->setDeductionParams($deduction);

        $deduction = $this->getEmployeeService()
            ->getEmployeeDeductionDao()
            ->saveEmployeeDeduction($deduction);

        return new EndpointResourceResult(EmployeeDeductionModel::class, $deduction);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)),
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            ...$this->getCommonBodyValidationRules(),
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResourceResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        $ids = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            CommonParams::PARAMETER_IDS
        );

        $this->getEmployeeService()
            ->getEmployeeDeductionDao()
            ->deleteEmployeeDeductions($empNumber, $ids);

        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_EMP_NUMBER, new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)),
            new ParamRule(CommonParams::PARAMETER_IDS, new Rule(Rules::ARRAY_TYPE))
        );
    }

    /**
     * @param EmployeeDeduction $deduction
     */
    private function setDeductionParams(EmployeeDeduction $deduction): void
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );

        if ($this->getRequestParams()->hasRequestParam(self::PARAMETER_NAME)) {
            $deduction->setName(
                $this->getRequestParams()->getString(
                    RequestParams::PARAM_TYPE_BODY,
                    self::PARAMETER_NAME
                )
            );
        }

        if ($this->getRequestParams()->hasRequestParam(self::PARAMETER_AMOUNT)) {
            $deduction->setAmount(
                $this->getRequestParams()->getString(
                    RequestParams::PARAM_TYPE_BODY,
                    self::PARAMETER_AMOUNT
                )
            );
        }

        if ($this->getRequestParams()->hasRequestParam(self::PARAMETER_COMMENT)) {
            $deduction->setComment(
                $this->getRequestParams()->getStringOrNull(
                    RequestParams::PARAM_TYPE_BODY,
                    self::PARAMETER_COMMENT
                )
            );
        }

        if ($this->getRequestParams()->hasRequestParam(self::PARAMETER_EFFECTIVE_DATE)) {
            $effectiveDate = $this->getRequestParams()->getDateTime(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EFFECTIVE_DATE
            );
            $deduction->setEffectiveDate($effectiveDate);
        }

        $employee = $this->getEmployeeService()->getEmployeeByEmpNumber($empNumber);
        $deduction->setEmployee($employee);
    }

    /**
     * @return array
     */
    private function getCommonBodyValidationRules(): array
    {
        return [
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_NAME,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_NAME_MAX_LENGTH])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_AMOUNT,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::BETWEEN, [self::PARAM_RULE_AMOUNT_MIN, self::PARAM_RULE_AMOUNT_MAX])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_COMMENT,
                    new Rule(Rules::STRING_TYPE)
                ),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_EFFECTIVE_DATE,
                    new Rule(Rules::API_DATE)
                )
            ),
        ];
    }
}
