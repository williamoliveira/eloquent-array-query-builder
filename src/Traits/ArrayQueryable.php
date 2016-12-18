<?php

namespace Williamoliveira\ArrayQueryBuilder\Traits;

use Williamoliveira\ArrayQueryBuilder\ArrayBuilder;

trait ArrayQueryable
{

    /**
     * @param array $arrayQuery
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function arrayQuery(array $arrayQuery)
    {
        $query = static::query();

        return (new ArrayBuilder())->apply($query, $arrayQuery);
    }

    /**
     * @param array $arrayQuery
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newArrayQuery(array $arrayQuery)
    {
        $query = $this->newQuery();

        return (new ArrayBuilder())->apply($query, $arrayQuery);
    }
}
