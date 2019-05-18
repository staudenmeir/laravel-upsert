<?php

namespace Staudenmeir\LaravelUpsert\Connections;

use Illuminate\Database\SqlServerConnection as Base;

class SqlServerConnection extends Base
{
    use CreatesQueryBuilder;
}
