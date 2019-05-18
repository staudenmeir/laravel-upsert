<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Staudenmeir\LaravelUpsert\Eloquent\HasUpsertQueries;

class User extends Model
{
    use HasUpsertQueries;

    protected $table = 'users';
}
