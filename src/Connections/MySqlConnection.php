<?php

namespace Staudenmeir\LaravelUpsert\Connections;

use Illuminate\Database\MySqlConnection as Base;

class MySqlConnection extends Base
{
    use CreatesQueryBuilder;
}
