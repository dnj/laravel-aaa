<?php

namespace dnj\AAA\Contracts;

interface IType extends IHasAbilities
{
    public function getId(): int;

    public function getLocalizedDetails(string $lang): ?ITypeLocalizedDetails;

    /**
     * @return string[]
     */
    public function getAbilities(): array;

    /**
     * @return int[]
     */
    public function getChildIds(): array;

    /**
     * @return int[]
     */
    public function getParentIds(): array;

    /**
     * @return array<mixed,mixed>
     */
    public function getMeta(): array;
}
