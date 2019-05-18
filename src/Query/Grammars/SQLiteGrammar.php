<?php

namespace Staudenmeir\LaravelUpsert\Query\Grammars;

use Illuminate\Database\Query\Grammars\SQLiteGrammar as Base;

class SQLiteGrammar extends Base
{
    use CompilesUpsertQueries;
}
