<?php

namespace dnj\AAA\Tests\Feature;

use dnj\AAA\Models\Type;
use dnj\AAA\Models\User;
use dnj\AAA\Rules\OwnerableModelExists;
use dnj\AAA\Tests\Doubles\DummyManager;
use dnj\AAA\Tests\Doubles\DummyModel;
use dnj\AAA\Tests\TestCase;

class OwnerableExistsRuleTest extends TestCase
{
    public function testExisting(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/Doubles/migrations');

        $myType = Type::factory()
            ->has(Type::factory(1), 'children')
            ->create();
        $me = User::factory()->withType($myType)->create();
        $child = User::factory()->withType($myType->children->first())->create();

        $model = DummyModel::query()->create(['owner_id' => $child->id]);
        $rule = new OwnerableModelExists(new DummyManager());
        $rule->userHasAccess($me);

        $called = false;
        $rule('dummy', $model->id, function ($message) use (&$called) {
            $called = true;
        });
        $this->assertFalse($called);

        $model = DummyModel::query()->create(['owner_id' => $me->id]);
        $called = false;
        $rule('dummy', $model->id, function ($message) use (&$called) {
            $called = true;
        });
        $this->assertFalse($called);
    }

    public function testNonAuthorized(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/Doubles/migrations');

        $me = User::factory()->create();
        $other = User::factory()->create();
        $rule = new OwnerableModelExists(new DummyManager());
        $rule->userHasAccess($me);
        $model = DummyModel::query()->create(['owner_id' => $other->id]);
        $called = false;
        $rule('dummy', $model->id, function ($message) use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);
    }

    public function testNonExisting(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/Doubles/migrations');

        $called = false;
        $rule = new OwnerableModelExists(new DummyManager());
        $rule('dummy', -1, function ($message) use (&$called) {
            $called = true;
        });
        $this->assertTrue($called);
    }

    public function testInvalidId(): void
    {
        $called = false;
        $rule = new OwnerableModelExists(new DummyManager());
        $rule('dummy', [-1], function ($message) use (&$called) {
            $called = true;
            $this->assertStringContainsString('numeric', $message);
        });
        $this->assertTrue($called);
    }
}
