<?php

namespace OrangeHRM\Payroll\Api;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Request;
use OrangeHRM\Core\Api\V2\Serializer\NormalizeException;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Payroll\Api\Model\EmployeePayrollOverviewModel;
use OrangeHRM\Payroll\Dao\EmployeePayrollOverviewDao;
use OrangeHRM\Payroll\Service\EmployeePayrollOverviewService;

class EmployeePayrollOverviewAPI extends Endpoint implements CrudEndpoint
{
    private EmployeePayrollOverviewService $service;

    /**
     * @throws NormalizeException
     */
    public function getAll(): EndpointCollectionResult
    {
        $params = $this->getRequestParams();

        $filters = [
            'employeeName' => $params->get('employeeName'),
            'department' => $params->get('department')
        ];

        $offset = ($params->get('page', 1) - 1) * $params->get('limit', 20);
        $limit = $params->get('limit', 20);

        $sort = [
            'field' => $params->get('sortField', 'e.emp_lastname'),
            'order' => $params->get('sortOrder', 'ASC')
        ];

        $service = $this->getEmployeePayrollOverviewService();
        $result = $service->getEmployees($filters, $offset, $limit, $sort);

        return new EndpointCollectionResult(
            EmployeePayrollOverviewModel::class,
            $result['data'],
            $result['total']
        );
    }

    private function getEmployeePayrollOverviewService(): EmployeePayrollOverviewService
    {
        if (!isset($this->service)) {
            $dao = new EmployeePayrollOverviewDao();
            $this->service = new EmployeePayrollOverviewService($dao);
        }
        return $this->service;
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        $rules = [
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    'limit',
                    new Rule(Rules::ZERO_OR_POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    'page',
                    new Rule(Rules::ZERO_OR_POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    'offset',
                    new Rule(Rules::ZERO_OR_POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    'sortField',
                    new Rule(Rules::STRING_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    'sortOrder',
                    new Rule(Rules::STRING_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    'employeeName',
                    new Rule(Rules::STRING_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    'department',
                    new Rule(Rules::STRING_TYPE)
                )
            ),
        ];

        return new ParamRuleCollection(...$rules);

    }

    public function create(): EndpointResult
    {
        // TODO: Implement create() method.
        return new EndpointCollectionResult(EmployeePayrollOverviewModel::class, []);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        // TODO: Implement getValidationRuleForCreate() method.
        return new ParamRuleCollection();
    }

    public function delete(): EndpointResult
    {
        // TODO: Implement delete() method.
        return new EndpointCollectionResult(EmployeePayrollOverviewModel::class, []);
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        // TODO: Implement getValidationRuleForDelete() method.
        return new ParamRuleCollection();
    }

    public function getOne(): EndpointResult
    {
        // TODO: Implement getOne() method.
        return new EndpointCollectionResult(EmployeePayrollOverviewModel::class, []);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        // TODO: Implement getValidationRuleForGetOne() method.
        return new ParamRuleCollection();
    }

    public function update(): EndpointResult
    {
        // TODO: Implement update() method.
        return new EndpointCollectionResult(EmployeePayrollOverviewModel::class, []);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        // TODO: Implement getValidationRuleForUpdate() method.
        return new ParamRuleCollection();
    }
}

