<?php

namespace dnj\AAA;

use dnj\AAA\Contracts\IOwnerableModel;
use dnj\AAA\Contracts\ITypeManager;
use dnj\AAA\Contracts\IUser;
use dnj\AAA\Contracts\IUserManager;
use dnj\AAA\Models\User;
use Illuminate\Auth\Access\Response;

abstract class Policy
{
    public static function getModelAbilityName(object|string $model, string $method): string
    {
        if ($model instanceof object) {
            $model = get_class($model);
        }

        return "{$model}@{$method}";
    }

    public function __call(string $method, array $arguments): Response
    {
        return $this->accessProcessor(
            $this->getAbilityName($method),
            $this->getUserFromArgs($arguments),
            $this->getModelFromArgs($arguments),
        );
    }

    abstract public function getModel(): string;

    public function getAbilityName(string $method): string
    {
        return self::getModelAbilityName($this->getModel(), $method);
    }

    protected function accessProcessor(string $ability, ?IUser $user, ?object $model): Response
    {
        if (!$user) {
            /**
             * @var ITypeManager
             */
            $typeManager = app(ITypeManager::class);
            $guest = $typeManager->getGuestType();
            if ($guest and $guest->can($ability)) {
                return Response::allow();
            }

            return $this->denyResponse($ability);
        }
        if ($user->cant($ability)) {
            return $this->denyResponseWhenUserCant($ability);
        }
        if (null === $model) {
            return Response::allow();
        }

        if (str_ends_with($ability, '@viewAnonymous')) {
            return Response::allow();
        }

        return $this->userHasAccessToModel($user, $model) ? Response::allow() : $this->denyResponse($ability);
    }

    protected function userHasAccessToModel(IUser $user, object $model): ?bool
    {
        if (!$model instanceof IOwnerableModel) {
            return true;
        }
        $ownerId = $model->getOwnerUserId();
        if (null === $ownerId) {
            return false;
        }
        if ($user->getId() == $ownerId) {
            return true;
        }
        if ($user instanceof User) {
            /**
             * @var IType
             */
            $type = $user->type;
        } else {
            /**
             * @var ITypeManager
             */
            $typeManager = app(ITypeManager::class);
            $type = $typeManager->findOrFail($user->getTypeId());
        }

        /**
         * @var IUserManager
         */
        $userManager = app(IUserManager::class);
        $owner = $userManager->findOrFail($ownerId);

        return in_array($owner->getTypeId(), $type->getChildIds());
    }

    protected function denyResponse(string $ability): Response
    {
        return Response::denyAsNotFound(
            sprintf("Sorry, You has no access to ability '%s'", $ability)
        );
    }

    protected function denyResponseWhenUserCant(string $ability): Response
    {
        return Response::denyWithStatus(403, sprintf("Sorry You has no access to ability '%s'", $ability));
    }

    private function getUserFromArgs(array $arguments): ?IUser
    {
        $user = $arguments[0] ?? null;
        if (null !== $user and !$user instanceof IUser) {
            throw new \InvalidArgumentException(sprintf('The argument #1 should be instance of %s but %s given!', IUser::class, get_class($user)));
        }

        return $user;
    }

    private function getModelFromArgs(array $arguments): ?object
    {
        $index = 1;
        if (!isset($arguments[$index]) or !$arguments[$index]) {
            return null;
        }
        if (!is_object($arguments[$index])) {
            throw new \InvalidArgumentException(sprintf('The argument #%s should be object, but %s given!', $index + 1, gettype($arguments[$index])));
        }
        $model = $this->getModel();
        if (!is_a($arguments[$index], $model, true)) {
            throw new \InvalidArgumentException(sprintf('The argument #%s should be instance of %s but %s given!', $index + 1, $model, get_class($arguments[$index])));
        }

        return $arguments[$index];
    }
}
