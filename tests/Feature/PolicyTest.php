<?php

namespace dnj\AAA\Tests\Feature;

use dnj\AAA\Models\Type;
use dnj\AAA\Models\TypeAbility;
use dnj\AAA\Models\User;
use dnj\AAA\Policy;
use dnj\AAA\Tests\Doubles\DummyModel;
use dnj\AAA\Tests\Doubles\DummyPolicy;
use dnj\AAA\Tests\Doubles\NoOwnerableModel;
use dnj\AAA\Tests\Doubles\NoOwnerablePolicy;
use dnj\AAA\Tests\TestCase;

class PolicyTest extends TestCase
{
    public function test(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/Doubles/migrations');

        $userType = Type::factory()
            ->has(TypeAbility::factory()->withName(Policy::getModelAbilityName(DummyModel::class, 'view')), 'abilities')
            ->has(TypeAbility::factory()->withName(Policy::getModelAbilityName(DummyModel::class, 'update')), 'abilities')
            ->has(Type::factory(1), 'children')
            ->create();

        $guestType = Type::factory()
            ->has(TypeAbility::factory()->withName(Policy::getModelAbilityName(DummyModel::class, 'view')), 'abilities')
            ->create();

        $user = User::factory()
            ->withType($userType)
            ->create();

        $subUserType = $userType->children->first();
        $subUser = User::factory()
            ->withType($subUserType)
            ->create();

        config()->set('aaa.guestType', null);
        $policy = app()->make(DummyPolicy::class);
        $this->assertFalse($policy->view()->allowed());

        config()->set('aaa.guestType', $guestType->id);
        $this->assertTrue($policy->view()->allowed());

        $this->assertFalse($policy->delete($user)->allowed());

        $this->assertTrue($policy->update($user)->allowed());

        $dummy = DummyModel::query()->create(['owner_id' => null]);
        $this->assertFalse($policy->update($user, $dummy)->allowed());

        $dummy = DummyModel::query()->create(['owner_id' => $user->id]);
        $this->assertTrue($policy->update($user, $dummy)->allowed());

        $dummy = DummyModel::query()->create(['owner_id' => $subUser->id]);
        $this->assertTrue($policy->update($user, $dummy)->allowed());
    }

    public function testNoOwnerableModel(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__) . '/Doubles/migrations');

        $userType = Type::factory()
            ->has(TypeAbility::factory()->withName(Policy::getModelAbilityName(NoOwnerableModel::class, 'view')), 'abilities')
            ->create();

        $user = User::factory()
            ->withType($userType)
            ->create();

        $policy = app()->make(NoOwnerablePolicy::class);
        $dummy = NoOwnerableModel::query()->create([]);
        $this->assertTrue($policy->view($user, $dummy)->allowed());
    }
}
