<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ConflictError;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ForbiddenError;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\RequireApproval;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\ConditionTreeParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;

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
        if ($this->request->input('data.attributes.signed_approval_request') === null) {
            return $this->canTrigger();
        } else {
            return $this->canApprove();
        }
    }

    private function canApprove(): bool
    {
        if (
            in_array($this->roleId, $this->smartAction['userApprovalEnabled'], true)
            && (empty($this->smartAction['userApprovalConditions']) || $this->matchConditions('userApprovalConditions'))
            && ($this->request->input('data.attributes.requester_id') !== $this->caller->getId() ||
                in_array($this->roleId, $this->smartAction['selfApprovalEnabled'], true))
        ) {
            return true;
        }

        throw new ForbiddenError('You don\'t have the permission to trigger this action.', [], 'CustomActionTriggerForbiddenError');
    }

    private function canTrigger(): bool
    {
        if (in_array($this->roleId, $this->smartAction['triggerEnabled'], true) &&
            ! in_array($this->roleId, $this->smartAction['approvalRequired'], true)) {
            if (empty($this->smartAction['triggerConditions']) || $this->matchConditions('triggerConditions')) {
                return true;
            }
        } elseif (in_array($this->roleId, $this->smartAction['approvalRequired'], true)
            && in_array($this->roleId, $this->smartAction['triggerEnabled'], true)
        ) {
            if (empty($this->smartAction['approvalRequiredConditions']) || $this->matchConditions('approvalRequiredConditions')) {
                throw new RequireApproval('This action requires to be approved.', [], 'CustomActionRequiresApprovalError', $this->smartAction['userApprovalEnabled']);
            } else {
                if (empty($this->smartAction['triggerConditions']) || $this->matchConditions('triggerConditions')) {
                    return true;
                }
            }
        }

        throw new ForbiddenError('You don\'t have the permission to trigger this action.', [], 'CustomActionTriggerForbiddenError');
    }

    private function matchConditions(string $conditionName): bool
    {
        try {
            $pk = SchemaUtils::getPrimaryKeys($this->collection)[0];
            if ($this->request->input('data.attributes.all_records')) {
                $conditionRecordFilter = new ConditionTreeLeaf($pk, Operators::NOT_EQUAL, $this->request->input('data.attributes.all_records_ids_excluded'));
            } else {
                $conditionRecordFilter = new ConditionTreeLeaf($pk, Operators::IN, $this->request->input('data.attributes.ids'));
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
            throw new ConflictError('The conditions to trigger this action cannot be verified. Please contact an administrator.', [], 'InvalidActionConditionError');
        }
    }
}
