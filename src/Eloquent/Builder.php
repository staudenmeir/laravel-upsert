<?php

namespace Staudenmeir\LaravelUpsert\Eloquent;

use Illuminate\Database\Eloquent\Builder as Base;

class Builder extends Base
{
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

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        if (is_null($update)) {
            $update = array_keys(reset($values));
        }

        $values = $this->addTimestampsToValues($values);

        $update = $this->addUpdatedAtToColumns($update);

        return $this->query->upsert($values, $target, $update);
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

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $values = $this->addTimestampsToValues($values);

        return $this->query->insertIgnore($values, $target);
    }

    /**
     * Add timestamps to the inserted values.
     *
     * @param array $values
     * @return array
     */
    protected function addTimestampsToValues(array $values)
    {
        if (!$this->model->usesTimestamps()) {
            return $values;
        }

        $timestamp = $this->model->freshTimestampString();

        $columns = array_filter([$this->model->getCreatedAtColumn(), $this->model->getUpdatedAtColumn()]);

        foreach ($columns as $column) {
            foreach ($values as &$row) {
                $row = array_merge([$column => $timestamp], $row);
            }
        }

        return $values;
    }

    /**
     * Add the "updated at" column to the updated columns.
     *
     * @param array $update
     * @return array
     */
    protected function addUpdatedAtToColumns(array $update)
    {
        if (!$this->model->usesTimestamps()) {
            return $update;
        }

        $column = $this->model->getUpdatedAtColumn();

        if (!is_null($column) && !array_key_exists($column, $update) && !in_array($column, $update)) {
            $update[] = $column;
        }

        return $update;
    }
}
