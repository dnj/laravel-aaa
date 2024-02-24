<?php

namespace dnj\AAA\Tests;

use dnj\AAA\Contracts\ITypeManager;
use dnj\AAA\Contracts\IUser;
use dnj\AAA\Contracts\IUserManager;
use dnj\AAA\Models\Type;
use dnj\AAA\Models\TypeAbility;
use dnj\AAA\Models\TypeTranslate;
use dnj\AAA\Models\User;
use dnj\AAA\Policy;
use dnj\AAA\TypeManager;
use dnj\AAA\UserManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Orchestra\Testbench\Concerns\WithWorkbench;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    public function getTypeManager(): TypeManager
    {
        return $this->app->make(ITypeManager::class);
    }

    public function getUserManager(): UserManager
    {
        return $this->app->make(IUserManager::class);
    }

    protected function createUserWithAbility(string $ability): IUser
    {
        $myType = Type::factory()
            ->has(TypeAbility::factory()->withName($ability), 'abilities')
            ->has(TypeTranslate::factory()->withLocale(App::getLocale()), 'translates')
            ->create();

        return User::factory()->withType($myType)->create();
    }


    protected function createUserWithModelAbility(string $model, string $ability): IUser
    {
        return $this->createUserWithAbility(Policy::getModelAbilityName($model, $ability));
    }
}
