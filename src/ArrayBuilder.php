<?php namespace Williamoliveira\ArrayQueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ArrayBuilder
{

    protected $aliases = [
        'eq' => '=',
        'neq' => '<>',
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'nlike' => 'not like',
        'nin' => 'not in',
        'notnull' => 'not null',
        'nn' => 'not null',
        'inq' => 'in'
    ];

    /**
     * @param Builder $query
     * @param array $arrayQuery
     *
     * Needs an array in the example format:
     *
     *  $exampleArrayQuery = [
     *      'where' => [
     *          [
     *              'name' => ['eq' => '%joao%']
     *          ],
     *          'created_at' => [
     *              'between'  => [
     *                   '2014-10-10',
     *                   '2015-10-10'
     *              ]
     *          ],
     *      ],
     *      'fields' => ['id', 'name', 'created_at'], // the columns to bring
     *      'order' => 'name'
     *      'include' => [                            // relations, can have where, order and fields
     *          'permissions',
     *          'roles' => [
     *              'where' => [
     *                  'name' => 'admin'
     *              ],
     *              'order' => 'name DESC'
     *              'fields' => ['id', 'name']
     *          ]
     *      ]
     *  ];
     *
     * @return mixed
     */
    public function apply(Builder &$query, array $arrayQuery)
    {

        if (isset($arrayQuery['where'])) {
            $this->buildWheres($query, $arrayQuery['where']);
        }

        if (isset($arrayQuery['fields'])) {
            $this->buildFields($query, $arrayQuery['fields']);
        }

        if (isset($arrayQuery['order'])) {
            $this->buildOrderBy($query, $arrayQuery['order']);
        }

        if (isset($arrayQuery['include'])) {
            $this->buildIncludes($query, $arrayQuery['include']);
        }

        return $query;
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param array $wheres
     */
    protected function buildWheres(&$queryBuilder, array $wheres)
    {
        foreach ($wheres as $whereField => $where) {

            if (!isset($whereField) || !isset($where)) {
                return;
            }

            $whereOperator = is_array($where) ? array_keys($where)[0] : '=';
            $whereValue = is_array($where) ? $where[$whereOperator] : $where;

            $this->buildWhere($queryBuilder, $whereField, $whereOperator, $whereValue);
        }

    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param $field
     * @param $operator
     * @param $value
     * @return void
     */
    protected function buildWhere(&$queryBuilder, $field, $operator, $value)
    {
        $operator = $this->parseOperator($operator);

        if (strpos($field, '.') > -1) {
            $this->buildWhereHas($queryBuilder, $field, $operator, $value);
            return;
        }

        switch($operator){
            case 'between':
                $queryBuilder->whereBetween($field, [$value[0], $value[1]]); return;
            case 'not null':
                $queryBuilder->whereNotNull($field); return;
            case 'in':
                $queryBuilder->whereIn($field, (!is_array($value) ? [$value] : $value)); return;
            case 'not in':
                $queryBuilder->whereNotIn($field, (!is_array($value) ? [$value] : $value)); return;
            case 'search':
                $this->buildTextSearchWhere($queryBuilder, $field, $value); return;
        }

        $queryBuilder->where($field, $operator, $value);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param array $columns
     */
    protected function buildFields(&$queryBuilder, $columns = ['*'])
    {
        $queryBuilder->select($columns);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param $order
     */
    protected function buildOrderBy(&$queryBuilder, $order)
    {
        $order = strtolower($order);

        $orderBy = str_replace([' asc', ' desc'], '', $order);
        $orderDirection = ends_with($order, ' desc') ? 'desc' : 'asc';

        $queryBuilder->orderBy($orderBy, $orderDirection);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param array $includes
     */
    protected function buildIncludes(&$queryBuilder, array $includes)
    {
        $builtIncludes = [];

        foreach ($includes as $includeName => $include) {

            if (empty($include['where']) && empty($include['fields']) && empty($include['order'])) {
                $builtIncludes[] = $includeName;
                continue;
            }

            $builtIncludes[$includeName] = function ($query) use ($include) {

                if (isset($include['where'])) {
                    $this->buildWheres($query, $include['where']);
                }

                if (isset($include['fields'])) {
                    $this->buildFields($query, $include['fields']);
                }

                if (isset($include['order'])) {
                    $this->buildOrderBy($query, $include['order']);
                }

            };
        }

        $queryBuilder->with($builtIncludes);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param $field
     * @param $value
     */
    protected function buildTextSearchWhere(&$queryBuilder, $field, $value)
    {
        $value = preg_replace('/\s\s+/', ' ', trim($value));
        $value = '%' . str_replace(' ', '%', $value) . '%';

        $queryBuilder->where($field, 'like', $value);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param $hasField
     * @param $operator
     * @param $value
     */
    protected function buildWhereHas(&$queryBuilder, $hasField, $operator, $value)
    {
        $reversedParts = explode('.', strrev($hasField), 2);

        $hasField = strrev($reversedParts[1]);
        $field = strrev($reversedParts[0]);

        $queryBuilder->whereHas($hasField, function ($query) use ($queryBuilder, $field, $operator, $value) {
            $this->buildWhere($query, $field, $operator, $value);
        });
    }

    protected function parseOperator($operator)
    {
        return strtolower($this->aliases[$operator] ?: $operator);
    }
}