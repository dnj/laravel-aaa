<?php

namespace dnj\AAA;

use dnj\AAA\Contracts\IType;
use dnj\AAA\Contracts\IUser;
use dnj\AAA\Contracts\IUserManager;
use dnj\AAA\Contracts\UserStatus;
use dnj\AAA\Models\User;
use dnj\AAA\Models\Username;
use dnj\UserLogger\Contracts\ILogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Support\Facades\DB;

class UserManager implements IUserManager
{
    public static function getUserId(int|IUser $user): int
    {
        if ($user instanceof IUser) {
            return $user->getId();
        }

        return $user;
    }

    public function __construct(protected ILogger $userLogger)
    {
    }

    public function find(int $userId): ?User
    {
        return User::query()->find($userId);
    }

    public function findOrFail(int $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    public function findByUsername(string $username): ?User
    {
        return User::query()->whereRelation('usernames', 'username', $username)->first();
    }

    public function findByUsernameOrFail(string $username): User
    {
        return User::query()->whereRelation('usernames', 'username', $username)->firstOrFail();
    }

    /**
     * @return Collection<User>
     */
    public function search(array $filters = []): Collection
    {
        return User::query()
            ->filter($filters)
            ->get();
    }

    public function count(array $filters = []): int
    {
        return User::query()
            ->filter($filters)
            ->count();
    }

    public function store(string $name, string $username, string $password, int|IType $type, array $meta = [], bool $userActivityLog = false): User
    {
        return DB::transaction(function () use ($name, $username, $password, $type, $meta, $userActivityLog) {
            $duplicateRecordsCount = Username::query()->where('username', $username)->exists();
            if ($duplicateRecordsCount > 0) {
                throw new MultipleRecordsFoundException($duplicateRecordsCount);
            }

            /**
             * @var User
             */
            $user = User::query()->create([
                'name' => $name,
                'type_id' => TypeManager::getTypeId($type),
                'meta' => $meta,
                'status' => UserStatus::ACTIVE,
            ]);
            $user->usernames()->create([
                'username' => $username,
                'password' => $password,
            ]);

            if ($userActivityLog) {
                $this->userLogger->on($user)
                    ->withRequest(request())
                    ->withProperties($user->toArray())
                    ->log('created');
            }

            return $user;
        });
    }

    public function update(int|IUser $user, array $changes, bool $userActivityLog = false): User
    {
        return DB::transaction(function () use ($user, $changes, $userActivityLog) {
            /**
             * @var User
             */
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail(User::ensureId($user));
            if (isset($changes['type'])) {
                $changes['type_id'] = TypeManager::getTypeId($changes['type']);
                unset($changes['type']);
            }
            if (isset($changes['usernames'])) {
                $user->updateUsernames($changes['usernames']);
                unset($changes['usernames']);
            }
            $user->fill($changes);
            $changes = $user->changesForLog();
            $user->save();
            $user->refresh();

            if ($userActivityLog) {
                $this->userLogger->on($user)
                    ->withRequest(request())
                    ->withProperties($changes)
                    ->log('updated');
            }

            return $user;
        });
    }

    public function ping(int|IUser $user): void
    {
        $user = User::ensureId($user);
        DB::transaction(function () use ($user) {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($user);
            $user->update(['ping_at' => now()]);
        });
    }

    public function destroy(int|IUser $user, bool $userActivityLog = false): void
    {
        DB::transaction(function () use ($user, $userActivityLog) {
            /**
             * @var User
             */
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail(User::ensureId($user));
            $user->delete();

            if ($userActivityLog) {
                $this->userLogger->on($user)
                    ->withRequest(request())
                    ->withProperties($user->toArray())
                    ->log('deleted');
            }
        });
    }

    public function isParentOf(int|IUser $user, int|IUser $other): bool
    {
        if (is_int($user)) {
            $user = User::query()->findOrFail($user);
        }
        if (is_int($other)) {
            $other = User::query()->findOrFail($other);
        }

        /*
         * @var User $user
         * @var User $other
         */

        return $user->type->isParentOf($other->type_id);
    }

    public function isChildOf(int|IUser $user, int|IUser $other): bool
    {
        if (is_int($user)) {
            $user = User::query()->findOrFail($user);
        }
        if (is_int($other)) {
            $other = User::query()->findOrFail($other);
        }

        /*
         * @var User $user
         * @var User $other
         */

        return $user->type->isChildOf($other->type_id);
    }
}
