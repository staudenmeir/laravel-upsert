<?php

namespace Staudenmeir\LaravelUpsert\Query\Grammars;

use Illuminate\Database\Query\Builder;

trait CompilesUpsertQueries
{
    /**
     * Compile an "upsert" statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $values
     * @param array $target
     * @param array $update
     * @return string
     */
    public function compileUpsert(Builder $query, array $values, array $target, array $update)
    {
        $sql = $this->compileInsert($query, $values);

        $sql .= ' on conflict ('.$this->columnize($target).') do update set ';

        $columns = collect($update)->map(function ($value, $key) {
            return is_numeric($key)
                ? $this->wrap($value).' = '.$this->wrapValue('excluded').'.'.$this->wrap($value)
                : $this->wrap($key).' = '.$this->parameter($value);
        })->implode(', ');

        return $sql.$columns;
    }

    /**
     * Compile an "insert ignore" statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $values
     * @param array $target
     * @return string
     */
    public function compileInsertIgnore(Builder $query, array $values, array $target)
    {
        return $this->compileInsert($query, $values).' on conflict do nothing';
    }
}
