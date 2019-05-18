<?php

namespace Staudenmeir\LaravelUpsert\Connections;

use Staudenmeir\LaravelUpsert\Query\Builder;

trait CreatesQueryBuilder
{
    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new Builder($this);
    }
}
