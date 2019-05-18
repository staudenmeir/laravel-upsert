<?php

namespace Staudenmeir\LaravelUpsert\Query;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as Base;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use RuntimeException;
use Staudenmeir\LaravelUpsert\Query\Grammars\MySqlGrammar;
use Staudenmeir\LaravelUpsert\Query\Grammars\PostgresGrammar;
use Staudenmeir\LaravelUpsert\Query\Grammars\SQLiteGrammar;
use Staudenmeir\LaravelUpsert\Query\Grammars\SqlServerGrammar;

class Builder extends Base
{
    /**
     * Create a new query builder instance.
     *
     * @param \Illuminate\Database\Connection $connection
     * @param \Illuminate\Database\Query\Grammars\Grammar|null $grammar
     * @param \Illuminate\Database\Query\Processors\Processor|null $processor
     * @return void
     */
    public function __construct(Connection $connection, Grammar $grammar = null, Processor $processor = null)
    {
        $grammar = $grammar ?: $connection->withTablePrefix($this->getQueryGrammar($connection));
        $processor = $processor ?: $connection->getPostProcessor();

        parent::__construct($connection, $grammar, $processor);
    }

    /**
     * Get the query grammar.
     *
     * @param \Illuminate\Database\Connection $connection
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getQueryGrammar(Connection $connection)
    {
        $driver = $connection->getDriverName();

        switch ($driver) {
            case 'mysql':
                return new MySqlGrammar;
            case 'pgsql':
                return new PostgresGrammar;
            case 'sqlite':
                return new SQLiteGrammar;
            case 'sqlsrv':
                return new SqlServerGrammar;
        }

        throw new RuntimeException('This database is not supported.'); // @codeCoverageIgnore
    }

    /**
     * Insert new records or update the existing ones.
     *
     * @param array $values
     * @param array|string $target
     * @param array|null $update
     * @return int
     */
    public function upsert(array $values, $target, array $update = null)
    {
        if (empty($values)) {
            return 0;
        }

        if ($update === []) {
            return (int) $this->insert($values);
        }

        $values = $this->prepareValuesForInsert($values);

        if (is_null($update)) {
            $update = array_keys(reset($values));
        }

        $bindings = $this->cleanBindings(
            array_merge(
                Arr::flatten($values, 1),
                collect($update)->reject(function ($value, $key) {
                    return is_int($key);
                })->all()
            )
        );

        return $this->connection->affectingStatement(
            $this->grammar->compileUpsert($this, $values, (array) $target, $update),
            $bindings
        );
    }

    /**
     * Insert a new record into the database and ignore duplicate-key errors.
     *
     * @param array $values
     * @param array|string|null $target
     * @return int
     */
    public function insertIgnore(array $values, $target = null)
    {
        if (empty($values)) {
            return 0;
        }

        $values = $this->prepareValuesForInsert($values);

        return $this->connection->affectingStatement(
            $this->grammar->compileInsertIgnore($this, $values, (array) $target),
            $this->cleanBindings(Arr::flatten($values, 1))
        );
    }

    /**
     * Prepare the values for an "insert" statement.
     *
     * @param array $values
     * @return array
     */
    protected function prepareValuesForInsert(array $values)
    {
        if (!is_array(reset($values))) {
            return [$values];
        }

        foreach ($values as $key => $value) {
            ksort($value);

            $values[$key] = $value;
        }

        return $values;
    }
}
