<?php

namespace dnj\AAA\Contracts;

interface ITypeManager
{
    public function find(int $id): ?IType;

    public function findOrFail(int $id): IType;

    /**
     * @param array{id?:int[],title?:string,hasFullAccess:bool} $filters
     *
     * @return iterable<IType>
     */
    public function search(array $filters = []): iterable;

    public function getGuestTypeId(): ?int;

    public function getGuestType(): ?IType;

    /**
     * @param array<string,array{title:string}> $translates
     * @param string[]                          $abilities
     * @param int[]                             $childIds
     * @param array<mixed,mixed>                $meta
     */
    public function store(
        array $translates,
        array $abilities = [],
        array $childIds = [],
        array $meta = [],
        bool $childToItself = false,
        bool $userActivityLog = false,
    ): IType;

    /**
     * @param array{translates?:array<string,array{title:string}>,abilities?:string[],childIds?:int[],meta?:array<mixed,mixed>} $changes
     */
    public function update(
        int|IType $type,
        array $changes,
        bool $userActivityLog = false,
    ): IType;

    public function destroy(int|IType $type, bool $userActivityLog = false): void;

    public function isParentOf(int|IType $type, int|IType $other): bool;

    public function isChildOf(int|IType $type, int|IType $other): bool;

    /**
     * @return string[]
     */
    public function getAllAbilities(): array;
}
