## Why this nonsence?

So you can have easy to use filters to filter your data, without the need to write long conditional queries by hand, very useful for APIs.

:warning: It still in alpha, isn't well tested and the API may change a bit so it's recommended to be used in production yet

## How to install

`composer require williamoliveira/eloquent-array-query-builder:dev-master`

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

## Tips

From a Javascript client you could use this package to convert query objects to query strings with ease: https://github.com/ljharb/qs
