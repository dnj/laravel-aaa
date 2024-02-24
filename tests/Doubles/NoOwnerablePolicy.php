<?php

namespace dnj\AAA\Tests\Doubles;

use dnj\AAA\Policy;

class NoOwnerablePolicy extends Policy
{
    public function getModel(): string
    {
        return NoOwnerableModel::class;
    }
}
