[![Travis CI Build Status](https://travis-ci.org/williamoliveira/eloquent-array-query-builder.svg?branch=master)](https://travis-ci.org/williamoliveira/eloquent-array-query-builder)
[![Latest Stable Version](https://poser.pugx.org/williamoliveira/eloquent-array-query-builder/v/stable)](https://packagist.org/packages/williamoliveira/eloquent-array-query-builder)
[![Total Downloads](https://poser.pugx.org/williamoliveira/eloquent-array-query-builder/downloads)](https://packagist.org/packages/williamoliveira/eloquent-array-query-builder)
[![License](https://poser.pugx.org/williamoliveira/eloquent-array-query-builder/license)](https://packagist.org/packages/williamoliveira/eloquent-array-query-builder)

## Why this nonsence?

So you can have easy to use filters to filter your data, without the need to write long conditional queries by hand, very useful for APIs.

## How to install

`composer require williamoliveira/eloquent-array-query-builder`
## How to use

We let the wiring of the request to the model to you, so you can use it wherever you want.

Example in a controller:
```php
public function index(Request $request, \Williamoliveira\ArrayQueryBuilder\ArrayBuilder $arrayBuilder)
{
    $query = User::query();
    $query = $arrayBuilder->apply($query, $request->all());
    
    return $query->paginate($request->get('per_page')); // Note it does not do pagination,
                                                        // you need to do it youserlf
}
```

You can also use the ArrayQueryable trait in your model:
```php
 // Model
 class User extends Model{
     use \Williamoliveira\ArrayQueryBuilder\ArrayQueryable;
 // ...

 // Usage
 return User::arrayQuery($request->all())->get(); //static
 return (new User())->newArrayQuery($request->all())->get(); //instance
```

#### Query format

Here is a example of what a query can look like:
```php
$exampleArrayQuery = [
    'where' => [
        'name' => ['like' => '%joao%'],
        'created_at' => [
            'between'  => [
                 '2014-10-10',
                 '2015-10-10'
            ]
        ],
        'or' => [                             // nested boolean where clauses
            'foo' => 'bar',
            'baz' => 'qux'
        ]
    ],
    'fields' => ['id', 'name', 'created_at'],
    'order' => 'name',
    'include' => [                            // relations, can have where, order and fields
        'permissions' => true,
        'roles' => [
            'where' => [
                'name' => 'admin'
            ],
            'fields' => ['id', 'name'],
            'order' => 'name DESC'
        ]
    ]
];
```

The same query as a query string:
```
/your-route?where[name][like]=%joao%
&where[created_at][between][]=2014-10-10
&where[created_at][between][]=2015-10-10
&where[or][foo]=bar
&where[or][baz]=qux
&fields[]=id
&fields[]=name
&fields[]=created_at
&order=name
&include[permissions]=true
&include[roles][where][name]=admin
&include[roles][fields][]=id
&include[roles][fields][]=name
&include[roles][order]=name DESC
```
