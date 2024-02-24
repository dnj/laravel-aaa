<?php

namespace dnj\AAA\Tests\Doubles;

use Illuminate\Database\Eloquent\Model;

class NoOwnerableModel extends Model
{
    protected $guarded = [];
    protected $table = 'aaa_noownerable';
}
