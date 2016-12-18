<?php

namespace Tests;

use Mockery as m;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Williamoliveira\ArrayQueryBuilder\ArrayBuilder;

class ArrayBuilderTest extends AbstractTestCase
{

    public function testEmptyQuery()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([]);

        $fluentQueryBuilder = $this->getQueryBuilder();

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testBasicWhere()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'where' => [
                'foo' => 'bar'
            ]
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->where('foo', 'bar');

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testWhereLike()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'where' => [
                'name' => ['like' => '%joao%']
            ],
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->where('name', 'like', '%joao%');

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testWhereILike()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'where' => [
                'name' => ['ilike' => '%joao%']
            ],
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->where('name', 'ilike', '%joao%');

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testWhereBetween()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'where' => [
                'created_at' => [
                    'between'  => [
                        '2014-10-10',
                        '2015-10-10'
                    ]
                ],
            ]
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->whereBetween('created_at', ['2014-10-10', '2015-10-10']);

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testWhereOr()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'where' => [
                'or' => [
                    'foo' => 'bar',
                    'baz' => 'qux'
                ]
            ]
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->whereNested(function (QueryBuilder $query) {
                $query->orWhere('foo', 'bar');
                $query->orWhere('baz', 'qux');
            });

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testWhereAnd()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'where' => [
                'and' => [
                    'foo' => 'bar',
                    'baz' => 'qux'
                ]
            ]
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->whereNested(function (QueryBuilder $query) {
                $query->where('foo', 'bar');
                $query->where('baz', 'qux');
            });

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testSelectFields()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'fields' => ['id', 'name', 'created_at']
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->select(['id', 'name', 'created_at']);

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testOrderBySingleField()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'order' => 'name',
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->orderBy('name');

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testOrderByManyFields()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'order' => ['id', 'name'],
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->orderBy('id')
            ->orderBy('name');

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }


    public function testOrderBySingleFieldWithDirection()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'order' => 'name ASC',
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->orderBy('name', 'ASC');

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testOrderByManyFieldsWithDirection()
    {
        $arrayQueryBuilder = $this->buildArrayQuery([
            'order' => ['id ASC', 'name DESC'],
        ]);

        $fluentQueryBuilder = $this->getQueryBuilder()
            ->orderBy('id', 'ASC')
            ->orderBy('name', 'DESC');

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testBasicInclude()
    {
        $arrayQueryBuilder = $this->buildArrayQueryFromEloquent([
            'include' => ['foo', 'bar']
        ]);

        $fluentQueryBuilder = $this->getEloquentBuilder()
            ->with(['foo', 'bar']);

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testIncludeWithWhere()
    {
        $arrayQueryBuilder = $this->buildArrayQueryFromEloquent([
            'include' => [
                'roles' => [
                    'where' => [
                        'name' => 'admin'
                    ]
                ]
            ]
        ]);

        $fluentQueryBuilder = $this->getEloquentBuilder()
            ->with([
                'roles' => function (QueryBuilder $query) {
                    $query->where('name', 'admin');
                }
            ]);

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testComplexIncludes()
    {
        $arrayQueryBuilder = $this->buildArrayQueryFromEloquent([
            'include' => [
                'permissions' => true,
                'roles' => [
                    'where' => [
                        'name' => 'admin'
                    ],
                    'fields' => ['id', 'name'],
                    'order' => 'name DESC'
                ]
            ]
        ]);

        $fluentQueryBuilder = $this->getEloquentBuilder()
            ->with([
                'permission' => true,
                'roles' => function (QueryBuilder $query) {
                    $query->select('id', 'name')
                        ->where('name', 'admin')
                        ->orderBy('name', 'DESC');
                }
            ]);

        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testComplexQueryFromReadmeExample()
    {
        $arrayQueryBuilder = $this->buildArrayQueryFromEloquent([
            'where' => [
                'name' => ['like' => '%joao%'],
                'created_at' => [
                    'between'  => [
                        '2014-10-10',
                        '2015-10-10'
                    ]
                ],
                'or' => [
                    'foo' => 'bar',
                    'baz' => 'qux'
                ]
            ],
            'fields' => ['id', 'name', 'created_at'],
            'order' => 'name',
            'include' => [
                'permissions' => true,
                'roles' => [
                    'where' => [
                        'name' => 'admin'
                    ],
                    'fields' => ['id', 'name'],
                    'order' => 'name DESC'
                ]
            ]
        ]);

        $fluentQueryBuilder = $this->getEloquentBuilder()
            ->select(['id', 'name', 'created_at'])
            ->orderBy('name')
            ->where('name', 'like', '%joao%')
            ->whereBetween('created_at', ['2014-10-10', '2015-10-10'])
            ->whereNested(function (QueryBuilder $query) {
                $query->orWhere('foo', 'bar');
                $query->orWhere('baz', 'qux');
            })
            ->with([
                'permission' => true,
                'roles' => function (QueryBuilder $query) {
                    $query->select('id', 'name')
                        ->where('name', 'admin')
                        ->orderBy('name', 'DESC');
                }
            ]);


        $this->assertQueryEquals($fluentQueryBuilder, $arrayQueryBuilder);
    }

    public function testArrayBuilderDoesNotAcceptsQueryBuilderWithIncludes()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->buildArrayQuery([
            'include' => ['foo', 'bar']
        ]);
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query1
     * @param EloquentBuilder|QueryBuilder $query2
     */
    protected function assertQueryEquals($query1, $query2)
    {
        $queryComponents1 = $this->toQueryComponentsArray($query1);
        $queryComponents2 = $this->toQueryComponentsArray($query2);

        $this->assertEquals($queryComponents1, $queryComponents2);
    }

    /**
     * @param array $arrayQuery
     * @return QueryBuilder
     */
    protected function buildArrayQuery($arrayQuery)
    {
        return (new ArrayBuilder())->apply($this->getQueryBuilder(), $arrayQuery);
    }

    /**
     * @param array $arrayQuery
     * @return EloquentBuilder|QueryBuilder
     */
    protected function buildArrayQueryFromEloquent($arrayQuery)
    {
        return (new ArrayBuilder())->apply($this->getEloquentBuilder(), $arrayQuery);
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        $grammar = new \Illuminate\Database\Query\Grammars\Grammar;
        $processor = m::mock(\Illuminate\Database\Query\Processors\Processor::class);

        return new QueryBuilder(
            m::mock(\Illuminate\Database\ConnectionInterface::class),
            $grammar,
            $processor
        );
    }

    /**
     * @return EloquentBuilder|QueryBuilder
     */
    protected function getEloquentBuilder()
    {
        return new EloquentBuilder($this->getQueryBuilder());
    }

    /**
     * @param EloquentBuilder|QueryBuilder $query
     * @return array
     */
    protected function toQueryComponentsArray($query)
    {
        if ($query instanceof EloquentBuilder) {
            $query = $query->getQuery();
        }

        $selectComponents = [
            'aggregate',
            'columns',
            'from',
            'joins',
            'wheres',
            'groups',
            'havings',
            'orders',
            'limit',
            'offset',
            'unions',
            'lock',
        ];

        $rawQueryComponents = [];

        foreach ($selectComponents as $component) {
            if (! is_null($query->$component)) {
                $rawQueryComponents[$component] = $query->$component;
            }
        }

        // TODO: improve? this is very hacky
        return json_decode(json_encode($rawQueryComponents), true);
    }
}
