<?php

namespace Tests\Unit;

use App\Automation\ConditionEvaluator;
use Tests\TestCase;

class ConditionEvaluatorTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new ConditionEvaluator();
    }

    public function test_empty_conditions_always_pass(): void
    {
        $this->assertTrue($this->evaluator->evaluate([], ['any' => 'context']));
    }

    public function test_equals_operator(): void
    {
        $conditions = [['field' => 'source', 'operator' => '=', 'value' => 'calculator']];
        $this->assertTrue($this->evaluator->evaluate($conditions, ['source' => 'calculator']));
        $this->assertFalse($this->evaluator->evaluate($conditions, ['source' => 'contact']));
    }

    public function test_not_equals_operator(): void
    {
        $conditions = [['field' => 'status', 'operator' => '!=', 'value' => 'closed']];
        $this->assertTrue($this->evaluator->evaluate($conditions, ['status' => 'open']));
        $this->assertFalse($this->evaluator->evaluate($conditions, ['status' => 'closed']));
    }

    public function test_greater_than_operator(): void
    {
        $conditions = [['field' => 'value', 'operator' => '>', 'value' => 1000]];
        $this->assertTrue($this->evaluator->evaluate($conditions,  ['value' => 2000]));
        $this->assertFalse($this->evaluator->evaluate($conditions, ['value' => 500]));
    }

    public function test_less_than_operator(): void
    {
        $conditions = [['field' => 'value', 'operator' => '<', 'value' => 1000]];
        $this->assertTrue($this->evaluator->evaluate($conditions,  ['value' => 500]));
        $this->assertFalse($this->evaluator->evaluate($conditions, ['value' => 2000]));
    }

    public function test_contains_operator(): void
    {
        $conditions = [['field' => 'notes', 'operator' => 'contains', 'value' => 'urgent']];
        $this->assertTrue($this->evaluator->evaluate($conditions,  ['notes' => 'This is urgent!']));
        $this->assertFalse($this->evaluator->evaluate($conditions, ['notes' => 'Nothing special']));
    }

    public function test_unknown_operator_passes(): void
    {
        $conditions = [['field' => 'x', 'operator' => '~~', 'value' => 'any']];
        $this->assertTrue($this->evaluator->evaluate($conditions, ['x' => 'anything']));
    }

    public function test_all_conditions_must_pass(): void
    {
        $conditions = [
            ['field' => 'source', 'operator' => '=',  'value' => 'calculator'],
            ['field' => 'value',  'operator' => '>',  'value' => 1000],
        ];
        $this->assertTrue($this->evaluator->evaluate($conditions,  ['source' => 'calculator', 'value' => 5000]));
        $this->assertFalse($this->evaluator->evaluate($conditions, ['source' => 'calculator', 'value' => 100]));
        $this->assertFalse($this->evaluator->evaluate($conditions, ['source' => 'contact',    'value' => 5000]));
    }

    public function test_missing_context_field_treated_as_null(): void
    {
        $conditions = [['field' => 'nonexistent', 'operator' => '=', 'value' => null]];
        $this->assertTrue($this->evaluator->evaluate($conditions, []));
    }
}
