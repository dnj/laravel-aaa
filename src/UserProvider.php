<?php

namespace dnj\AAA;

use dnj\AAA\Contracts\IUser;
use dnj\AAA\Contracts\IUserManager;
use dnj\AAA\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as AuthUserProvider;
use Illuminate\Contracts\Hashing\Hasher;

class UserProvider implements AuthUserProvider
{
    public function __construct(protected IUserManager $userManager, protected Hasher $hasher)
    {
    }

    public function retrieveById($identifier): ?IUser
    {
        return $this->userManager->find($identifier);
    }

    public function retrieveByToken($identifier, $token): ?IUser
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
    }

    public function retrieveByCredentials(array $credentials): ?IUser
    {
        if (!isset($credentials['username'])) {
            return null;
        }

        return $this->userManager->findByUsername($credentials['username']);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (!isset($credentials['username'], $credentials['password'])) {
            return false;
        }
        if (!$user instanceof User) {
            return false;
        }
        $username = $user->usernames()->where('username', $credentials['username'])->first();
        if (!$username) {
            return false;
        }
        $verified = $username->verifyPassword($credentials['password']);
        if ($verified) {
            $user->setActiveUsername($username);
        }

        return $verified;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('user must be an instance of '.User::class);
        }

        $username = $user->getActiveUsername();
        if (!$username) {
            return;
        }
        if (!$this->hasher->needsRehash($username->password) && !$force) {
            return;
        }
        $username->forceFill([
            'password' => $this->hasher->make($credentials['password']),
        ])->save();
    }

    public function getHasher(): Hasher
    {
        return $this->hasher;
    }

    /**
     * @return $this
     */
    public function setHasher(Hasher $hasher): static
    {
        $this->hasher = $hasher;

        return $this;
    }
}
