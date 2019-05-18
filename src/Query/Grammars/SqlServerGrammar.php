<?php

namespace Staudenmeir\LaravelUpsert\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SqlServerGrammar as Base;

class SqlServerGrammar extends Base
{
    use CompilesUpsertQueries;

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
        return $this->compileMerge($query, $values, $target, $update);
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
        return $this->compileMerge($query, $values, $target);
    }

    /**
     * Compile a "merge" statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $values
     * @param array $target
     * @param array|null $update
     * @return string
     */
    public function compileMerge(Builder $query, array $values, array $target, array $update = null)
    {
        $columns = $this->columnize(array_keys(reset($values)));

        $sql = 'merge '.$this->wrapTable($query->from).' ';

        $parameters = collect($values)->map(function ($record) {
            return '('.$this->parameterize($record).')';
        })->implode(', ');

        $sql .= 'using (values '.$parameters.') '.$this->wrapTable('laravel_source').' ('.$columns.') ';

        $on = collect($target)->map(function ($column) use ($query) {
            return $this->wrap('laravel_source.'.$column).' = '.$this->wrap($query->from.'.'.$column);
        })->implode(' and ');

        $sql .= 'on '.$on.' ';

        if ($update) {
            $update = collect($update)->map(function ($value, $key) {
                return is_numeric($key)
                    ? $this->wrap($value).' = '.$this->wrap('laravel_source.'.$value)
                    : $this->wrap($key).' = '.$this->parameter($value);
            })->implode(', ');

            $sql .= 'when matched then update set '.$update.' ';
        }

        $sql .= 'when not matched then insert ('.$columns.') values ('.$columns.');';

        return $sql;
    }
}
