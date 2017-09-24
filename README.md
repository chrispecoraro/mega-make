## Megamake for Laravel

Laravel's `php artisan make:` on steroids

`artisan mega:make Department` produces:


```php
 /**
     * Display a listing of the Department resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): ResourceCollection
    {
        return new DepartmentResourceCollection(Department::all());
    }

    /**
     * Store a newly created Department resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): Response
    {
        $input = $request->validate([
            // TODO
            // write validator
        ]);
        $department = Department::create($input->toArray());

        return response($department, 201);
    }

```

The `mega:make` artisan command creates most of the various pieces of a Laravel 5.5 entity, adding boilerplate code and `//TODO`'s, giving you an extra boost, eliminating extra typing.

* A Model
* A Model Factory
* A Seeder
* A Migration
* A Resource Controller
* A Single API Resource
* An API Resource Collection
* A Test

Authors:
* [Christopher Pecoraro](https://github.com/chrispecoraro) - [@chris__pecoraro](https://twitter.com/chris__pecoraro)
* [Dylan De Souza](https://github.com/dylan-dpc) - [@dpc_22](https://twitter.com/dpc_22)

_This package was created in loving memory of my father, Dr. George Anthony Pecoraro._
