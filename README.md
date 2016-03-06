## Install

`composer require williamoliveira/eloquent-array-query-builder`

## Why this nonsence?

So you can have easy to use filters to filter your data, without the need to write long conditional queries by hand, very useful for APIs.

## How to use

We let the wiring of the request to the model to you, so you can use it where you want

Example in a controller:
```php
    public function index(Request $request, ArrayBuilder $arrayBuilder)
    {
        $query = User::query();
        $query = $arrayBuilder->apply($query, $request->all());
        
        return $query->paginate($request->get('per_page')); // Note it does not do pagination,
                                                            // you need to do it youserlf
    }
```

#### Query format

Here is a example of what a query can look like:
```php
$exampleArrayQuery = [
        'where' => [
            [
                'name' => ['like' => '%joao%']
        ],
        'created_at' => [
            'between'  => [
                 '2014-10-10',
                 '2015-10-10'
            ]
        ],
        [
            'role.name' => 'admin'
        ]
    ],
    'fields' => ['id', 'name', 'created_at'],
    'order' => 'name',
    'include' => [                            // relations, can have where, order and fields
        'permissions',
        'roles' => [
            'where' => [
                'name' => 'admin'
            ],
            'order' => 'name DESC',
            'fields' => ['id', 'name']
        ]
    ]
];
```

The same query on a query string:
```
/your-route?where[0][name][like]=%joao%
&where[created_at][between][0]=2014-10-10
&where[created_at][between][1]=2015-10-10
&where[1][role.name]=admin
&fields[0]=id
&fields[1]=name
&fields[2]=created_at
&order=name
&include[0]=permissions
&include[roles][where][name]=admin
&include[roles][order]=name DESC
&include[roles][fields][0]=id
&include[roles][fields][1]=name
```