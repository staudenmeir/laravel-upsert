![CI](https://github.com/staudenmeir/laravel-upsert/workflows/CI/badge.svg)
[![Code Coverage](https://scrutinizer-ci.com/g/staudenmeir/laravel-upsert/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/laravel-upsert/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/staudenmeir/laravel-upsert/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/staudenmeir/laravel-upsert/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/staudenmeir/laravel-upsert/v/stable)](https://packagist.org/packages/staudenmeir/laravel-upsert)
[![Total Downloads](https://poser.pugx.org/staudenmeir/laravel-upsert/downloads)](https://packagist.org/packages/staudenmeir/laravel-upsert)
[![License](https://poser.pugx.org/staudenmeir/laravel-upsert/license)](https://packagist.org/packages/staudenmeir/laravel-upsert)

## Introduction
This Laravel extension adds support for INSERT & UPDATE (UPSERT) and INSERT IGNORE to the query builder and Eloquent.

Supports Laravel 5.5+.

## Compatibility

- MySQL 5.1+: [INSERT ON DUPLICATE KEY UPDATE](https://dev.mysql.com/doc/refman/en/insert-on-duplicate.html)
- MariaDB 5.1+: [INSERT ON DUPLICATE KEY UPDATE](https://mariadb.com/kb/en/library/insert-on-duplicate-key-update/) 
- PostgreSQL 9.5+: [INSERT ON CONFLICT](https://www.postgresql.org/docs/current/sql-insert.html#SQL-ON-CONFLICT) 
- SQLite 3.24.0+: [INSERT ON CONFLICT](https://www.sqlite.org/lang_UPSERT.html)
- SQL Server 2008+: [MERGE](https://docs.microsoft.com/sql/t-sql/statements/merge-transact-sql)
 
## Installation

    composer require staudenmeir/laravel-upsert:"^1.0"

## Usage

- [INSERT & UPDATE (UPSERT)](#insert--update-upsert)
- [INSERT IGNORE](#insert-ignore)
- [Eloquent](#eloquent)
- [Lumen](#lumen)

### INSERT & UPDATE (UPSERT)

Consider this `users` table with a unique `username` column:

```php
Schema::create('users', function (Blueprint $table) {
    $table->increments('id');
    $table->string('username')->unique();
    $table->boolean('active');
    $table->timestamps();
});
```

Use `upsert()` to insert a new user or update the existing one. In this example, an inactive user will be reactivated and the `updated_at` timestamp will be updated:

```php
DB::table('users')->upsert(
    ['username' => 'foo', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
    'username',
    ['active', 'updated_at']
);
```

Provide the values to be inserted as the first argument. This can be a single record or multiple records.

The second argument is the column(s) that uniquely identify records. All databases except SQL Server require these columns to have a `PRIMARY` or `UNIQUE` index.

Provide the columns to be the updated as the third argument (optional). By default, all columns will be updated. 
You can provide column names and key-value pairs with literals or raw expressions (see below).

As an example with a composite key and a raw expression, consider this table that counts visitors per post and day:

```php
Schema::create('stats', function (Blueprint $table) {
    $table->unsignedInteger('post_id');
    $table->date('date');
    $table->unsignedInteger('views');
    $table->primary(['post_id', 'date']);
});
```

Use `upsert()` to log visits. The query will create a new record per post and day or increment the existing view counter:

```php
DB::table('stats')->upsert(
    [
        ['post_id' => 1, 'date' => now()->toDateString(), 'views' => 1],
        ['post_id' => 2, 'date' => now()->toDateString(), 'views' => 1],
    ],
    ['post_id', 'date'],
    ['views' => DB::raw('stats.views + 1')]
);
```

### INSERT IGNORE

You can also insert records while ignoring duplicate-key errors:

```php
Schema::create('users', function (Blueprint $table) {
    $table->increments('id');
    $table->string('username')->unique();
    $table->timestamps();
});

DB::table('users')->insertIgnore([
    ['username' => 'foo', 'created_at' => now(), 'updated_at' => now()],
    ['username' => 'bar', 'created_at' => now(), 'updated_at' => now()],
]);
```

SQL Server requires a second argument with the column(s) that uniquely identify records:

```php
DB::table('users')->insertIgnore(
    ['username' => 'foo', 'created_at' => now(), 'updated_at' => now()],
    'username'
);
```

### Eloquent

You can use UPSERT and INSERT IGNORE queries with Eloquent models.

In Laravel 5.5–5.7, this requires the `HasUpsertQueries` trait:

```php
class User extends Model
{
    use \Staudenmeir\LaravelUpsert\Eloquent\HasUpsertQueries;
}

User::upsert(['username' => 'foo', 'active' => true], 'username', ['active']);

User::insertIgnore(['username' => 'foo']);
```

If the model uses timestamps, `upsert()` and `insertIgnore()` will automatically add timestamps to the inserted values. `upsert()` will also add `updated_at` to the updated columns.

### Lumen

If you are using Lumen, you have to instantiate the query builder manually:

```php
$builder = new \Staudenmeir\LaravelUpsert\Query\Builder(app('db')->connection());

$builder->from(...)->upsert(...);
```

In Eloquent, the `HasUpsertQueries` trait is required for *all* versions of Lumen.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE OF CONDUCT](CODE_OF_CONDUCT.md) for details.
