<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\ConditionTreeParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SmartActionChecker
{
    public function __construct(
        protected Request $request,
        protected CollectionContract $collection,
        protected array $smartAction,
        protected Caller $caller,
        protected int $roleId,
        protected Filter $filter
    ) {
    }

    public function canExecute(): bool
    {
        if ($this->request->input('data.attributes.signed_approval_request') === null &&
            ! in_array($this->roleId, $this->smartAction['userApprovalEnabled'], true)) {
            dd(1);

            return $this->canTrigger();
        } else {
            dd(2);

            return $this->canApprove();
        }
    }

    private function canApprove(): bool
    {
        if ((empty($this->smartAction['userApprovalConditions']) || $this->matchConditions('userApprovalConditions')) &&
            ($this->parameters['data']['attributes']['requester_id'] !== $this->caller->getId() ||
                in_array($this->roleId, $this->smartAction['selfApprovalEnabled'], true))
        ) {
            dd('approve 1');

            return true;
        }

        dd('approve 2');

        throw new HttpException(Response::HTTP_FORBIDDEN, 'You don\'t have the permission to trigger this action.');
        //todo raise ForestLiana::Ability::Exceptions::TriggerForbidden.new   //CustomActionTriggerForbiddenError
    }

    private function canTrigger(): bool
    {
        if (in_array($this->roleId, $this->smartAction['triggerEnabled'], true) &&
            ! in_array($this->roleId, $this->smartAction['approvalRequired'], true)) {
            if (empty($this->smartAction['triggerConditions']) || $this->matchConditions('triggerConditions')) {
                dd('trigger 1');

                return true;
            }
        } elseif (in_array($this->roleId, $this->smartAction['approvalRequired'], true)) {
            if (empty($this->smartAction['approvalRequiredConditions']) || $this->matchConditions('approvalRequiredConditions')) {
                dd('trigger 2');

                throw new HttpException(Response::HTTP_FORBIDDEN, 'This action requires to be approved.');
            //todo raise ForestLiana::Ability::Exceptions::RequireApproval.new(@smart_action['userApprovalEnabled'])   //CustomActionRequiresApprovalError
            } else {
                if (empty($this->smartAction['triggerConditions']) || $this->matchConditions('triggerConditions')) {
                    dd('trigger 3');

                    return true;
                }
            }
        }


        dd('trriger 4');

        throw new HttpException(Response::HTTP_FORBIDDEN, 'You don\'t have the permission to trigger this action.');
        //todo raise ForestLiana::Ability::Exceptions::TriggerForbidden.new   //CustomActionTriggerForbiddenError
    }

    private function matchConditions(string $conditionName): bool
    {
        try {
            if ($this->request->input('data.attributes.all_records')) {
                $conditionRecordFilter = new ConditionTreeLeaf('id', Operators::NOT_EQUAL, $this->request->input('data.attributes.all_records_ids_excluded'));
            } else {
                $conditionRecordFilter = new ConditionTreeLeaf('id', Operators::IN, $this->request->input('data.attributes.ids'));
            }

            $condition = $this->smartAction[$conditionName][0]['filter'];
            $conditionalFilter = $this->filter->override(conditionTree: ConditionTreeFactory::intersect([
                ConditionTreeParser::fromPLainObject($this->collection, $condition),
                $this->filter->getConditionTree(),
                $conditionRecordFilter,
            ]));

            $rows = $this->collection->aggregate($this->caller, $conditionalFilter, new Aggregation(operation: 'Count'));

            return ($rows[0]['value'] ?? 0) === count($this->request->input('data.attributes.ids'));
        } catch (\Exception $e) {
            throw new HttpException(Response::HTTP_CONFLICT, 'The conditions to trigger this action cannot be verified. Please contact an administrator.');
            // todo raise ForestLiana::Ability::Exceptions::ActionConditionError.new   //'InvalidActionConditionError'
        }
    }
}
