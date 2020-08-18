<?php

namespace Williamoliveira\ArrayQueryBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;

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
     * @param Builder|QueryBuilder $query
     * @param array $arrayQuery
     * @return Builder|QueryBuilder
     * @throws \InvalidArgumentException
     */
    public function apply($query, array $arrayQuery)
    {
        if (isset($arrayQuery['include'])) {
            if ($query instanceof QueryBuilder) {
                throw new \InvalidArgumentException(
                    QueryBuilder::class . " does not support relations, you need " . Builder::class . " for that."
                );
            }

            $this->buildIncludes($query, $arrayQuery['include']);
        }

        if (isset($arrayQuery['where'])) {
            $this->buildWheres($query, $arrayQuery['where']);
        }

        if (isset($arrayQuery['has'])) {
            $this->buildHas($query, $arrayQuery['has']);
        }

        if (isset($arrayQuery['scopes'])) {
            $this->buildScopes($query, $arrayQuery['scope']);
        }

        if (isset($arrayQuery['fields'])) {
            $this->buildFields($query, $arrayQuery['fields']);
        }

        if (isset($arrayQuery['order'])) {
            $this->buildOrderBy($query, $arrayQuery['order']);
        }

        if (isset($arrayQuery['limit'])) {
            $this->buildLimit($query, $arrayQuery['limit']);
        }

        if (isset($arrayQuery['offset'])) {
            $this->buildOffset($query, $arrayQuery['offset']);
        } elseif (isset($arrayQuery['skip'])) {
            // "skip" is an alias for "offset"
            $this->buildOffset($query, $arrayQuery['skip']);
        }

        if (isset($arrayQuery['groupBy'])) {
            $this->buildGroupBy($query, $arrayQuery['groupBy']);
        } elseif (isset($arrayQuery['group_by'])) {
            // "group_by" is an alias for "groupBy"
            $this->buildGroupBy($query, $arrayQuery['group_by']);
        }

        if (isset($arrayQuery['having'])) {
            $this->buildHavings($query, $arrayQuery['having']);
        }

        return $query;
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param array $wheres
     * @param string $boolean
     */
    protected function buildWheres($queryBuilder, array $wheres, $boolean = 'and')
    {
        foreach ($wheres as $whereField => $where) {
            if (!isset($whereField) || !isset($where)) {
                continue;
            }

            $whereField = strtolower($whereField);

            // Nested OR where
            // Example: 'or' => ['foo' => 'bar', 'x => 'y']
            if ($whereField === 'or') {
                $queryBuilder->whereNested(function ($queryBuilder) use ($where) {
                    $this->buildWheres($queryBuilder, $where, 'or');
                }, $boolean);

                continue;
            }

            // Nested AND where
            // Example: 'and' => ['foo' => 'bar', 'x => 'y']
            if ($whereField === 'and') {
                $queryBuilder->whereNested(function ($queryBuilder) use ($where) {
                    $this->buildWheres($queryBuilder, $where, 'and');
                }, $boolean);

                continue;
            }

            // Operator is present on query
            // Example: 'foo' => ['like' => '%bar%']
            if (is_array($where)) {
                foreach ($where as $whereOperator => $whereValue) {
                    $whereOperator = $this->parseOperator($whereOperator);
                    $this->buildWhere($queryBuilder, $whereField, $whereOperator, $whereValue, $boolean);
                }

                continue;
            }

            // Operator is omitted on query, assumes '='
            // Example: 'foo' => 'bar'
            $whereOperator = is_array($where) ? array_keys($where)[0] : '=';
            $whereValue = is_array($where) ? $where[$whereOperator] : $where;

            $whereOperator = $this->parseOperator($whereOperator);

            $this->buildWhere($queryBuilder, $whereField, $whereOperator, $whereValue, $boolean);
        }
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param string $field
     * @param string|null $operator
     * @param array|string $value
     * @param string $boolean
     */
    protected function buildWhere($queryBuilder, $field, $operator, $value, $boolean = 'and')
    {
        if (strpos($field, '.') > -1) {
            $this->buildWhereHas($queryBuilder, $field, $operator, $value, $boolean);
            return;
        }

        switch ($operator) {
            case 'between':
                $queryBuilder->whereBetween($field, [$value[0], $value[1]], $boolean);
                return;
            case 'not null':
                $queryBuilder->whereNotNull($field, $boolean);
                return;
            case 'in':
                $queryBuilder->whereIn($field, (!is_array($value) ? [$value] : $value), $boolean);
                return;
            case 'not in':
                $queryBuilder->whereNotIn($field, (!is_array($value) ? [$value] : $value), $boolean);
                return;
            case 'search':
                $this->buildTextSearchWhere($queryBuilder, $field, $value, $boolean);
                return;
            default:
                $queryBuilder->where($field, $operator, $value, $boolean);
        }
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param array $columns
     */
    protected function buildFields($queryBuilder, $columns = ['*'])
    {
        $queryBuilder->select($columns);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param array|string $order
     */
    protected function buildOrderBy($queryBuilder, $order)
    {
        if (is_array($order)) {
            foreach ($order as $orderItem) {
                $this->buildOrderBySingle($queryBuilder, $orderItem);
            }

            return;
        }

        $this->buildOrderBySingle($queryBuilder, $order);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param string $order
     */
    protected function buildOrderBySingle($queryBuilder, $order)
    {
        $order = strtolower($order);

        $orderBy = str_replace([' asc', ' desc'], '', $order);
        $orderDirection = $this->ends_with($order, ' desc') ? 'desc' : 'asc';

        $queryBuilder->orderBy($orderBy, $orderDirection);
    }

    protected function ends_with($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (substr($haystack, -strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param int $limit
     */
    protected function buildLimit($queryBuilder, $limit)
    {
        $queryBuilder->limit($limit);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param int $offset
     */
    protected function buildOffset($queryBuilder, $offset)
    {
        $queryBuilder->offset($offset);
    }

    /**
     * @param Builder $queryBuilder
     * @param array $includes
     */
    protected function buildIncludes($queryBuilder, array $includes)
    {
        $builtIncludes = [];

        foreach ($includes as $includeName => $include) {
            // Support for array includes, example: ['user', 'post']
            // If it's a single dimension array the key will be numeric
            $includeName = is_numeric($includeName) ? $include : $includeName;

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
     * @param string $boolean
     */
    protected function buildTextSearchWhere($queryBuilder, $field, $value, $boolean = 'and')
    {
        $value = preg_replace('/\s\s+/', ' ', trim($value));
        $value = '%' . strtolower(str_replace(' ', '%', $value)) . '%';

        $theQueryBuilder = $queryBuilder instanceof Builder
            ? $queryBuilder->getQuery()
            : $queryBuilder;
        $grammar = $theQueryBuilder->getGrammar();

        // Postgres
        if ($grammar instanceof PostgresGrammar) {
            $queryBuilder->where($field, 'ilike', $value, $boolean);
            return;
        }

        $column = $grammar->wrap($field);

        // MySql
        if ($grammar instanceof MySqlGrammar) {
            $queryBuilder->whereRaw($column . ' COLLATE utf8_general_ci like ?', [$value], $boolean);
            return;
        }

        // Others
        $queryBuilder->whereRaw('lower(' . $column . ') like ?', [$value], $boolean);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param $hasField
     * @param $operator
     * @param $value
     * @param string $boolean
     */
    protected function buildWhereHas($queryBuilder, $hasField, $operator, $value, $boolean = 'and')
    {
        $reversedParts = explode('.', strrev($hasField), 2);

        $hasField = strrev($reversedParts[1]);
        $field = strrev($reversedParts[0]);

        $queryBuilder->whereHas($hasField, function ($query) use ($queryBuilder, $field, $operator, $value, $boolean) {
            $this->buildWhere($query, $field, $operator, $value, $boolean);
        });
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param string|array $groupBy
     */
    protected function buildGroupBy($queryBuilder, $groupBy)
    {
        $queryBuilder->groupBy($groupBy);
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param array $havings
     * @param string $boolean
     */
    protected function buildHavings($queryBuilder, array $havings, $boolean = 'and')
    {
        foreach ($havings as $havingField => $having) {
            if (!isset($havingField) || !isset($having)) {
                continue;
            }

            $havingField = strtolower($havingField);

            // Operator is omitted on query, assumes '='
            // Example: 'foo' => 'bar'
            $havingOperator = is_array($having) ? array_keys($having)[0] : '=';
            $havingValue = is_array($having) ? $having[$havingOperator] : $having;

            $havingOperator = $this->parseOperator($havingOperator);

            $this->buildHaving($queryBuilder, $havingField, $havingOperator, $havingValue, $boolean);
        }
    }

    /**
     * @param Builder|QueryBuilder $queryBuilder
     * @param string $havingField
     * @param string $havingOperator
     * @param string $havingValue
     * @param string $boolean
     *
     * @internal param array|string $having
     */
    protected function buildHaving($queryBuilder, $havingField, $havingOperator, $havingValue, $boolean)
    {
        $queryBuilder->having($havingField, $havingOperator, $havingValue, $boolean);
    }

    /**
     * @param $queryBuilder
     * @param $relationships
     */
    protected function buildHas($queryBuilder, $relationships)
    {
        if (is_array($relationships)) {
            foreach ($relationships as $relationship) {
                $queryBuilder->has($relationship);
            }
        } else {
            $queryBuilder->has($relationships);
        }
    }

    /**
     * @param $queryBuilder
     * @param $scopes
     */
    protected function buildScopes($queryBuilder, $scopes)
    {
        if (is_array($scopes)) {
            foreach ($scopes as $scope) {
                $this->buildScope($queryBuilder, $scope);
            }
        } else {
            $this->buildScope($queryBuilder, $scopes);
        }
    }

    /**
     * @param $queryBuilder
     * @param $scopes
     */
    protected function buildScope($queryBuilder, $scope)
    {
        if (property_exists(QueryBuilder::class, 'validScopes') && in_array($scope, $this->validScopes)) {
            $queryBuilder->$scope();
        } else {
            throw new \InvalidArgumentException(
                "$scope is not listed in the " . QueryBuilder::class . " \$validScopes protected array."
            );
        }
    }

    /**
     * @param string $operator
     * @return string
     */
    protected function parseOperator($operator)
    {
        $operator = array_key_exists($operator, $this->aliases)
            ? $this->aliases[$operator]
            : $operator;

        return strtolower($operator);
    }
}
