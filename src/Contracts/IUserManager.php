<?php

namespace dnj\AAA\Contracts;

interface IUserManager
{
    public function find(int $id): ?IUser;

    public function findOrFail(int $id): IUser;

    public function findByUsername(string $username): ?IUser;

    public function findByUsernameOrFail(string $username): IUser;

    /**
     * @param array{ids?:[],type?:array<IType|int>|IType|int,name?:string,username?:string} $filters
     *
     * @return iterable<IUser>
     */
    public function search(array $filters = []): iterable;

    /**
     * @param array<mixed,mixed> $meta
     */
    public function store(
        string $name,
        string $username,
        string $password,
        int|IType $type,
        array $meta = [],
        bool $userActivityLog = false,
    ): IUser;

    /**
     * @param array{name?:string,type?:int|IType,meta?:array<mixed,mixed>,status?:UserStatus,usernames?:array<string,array{password?:string}>} $changes
     */
    public function update(int|IUser $user, array $changes, bool $userActivityLog = false): IUser;

    public function destroy(int|IUser $user, bool $userActivityLog = false): void;
}
