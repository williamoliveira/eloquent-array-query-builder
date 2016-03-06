<?php
namespace Williamoliveira\ArrayQueryBuilder;

trait ArrayQueryable
{
    /**
     * @param array $arrayQuery
     * @return mixed
     */
    public function arrayQuery(array $arrayQuery)
    {
        $query = $this->query();

        return (new ArrayBuilder())->apply($query, $arrayQuery);
    }
}