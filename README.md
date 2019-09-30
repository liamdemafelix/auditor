# Laravel Auditor

[![GitHub issues](https://img.shields.io/github/issues/liamdemafelix/laravel-auditor)](https://github.com/liamdemafelix/laravel-auditor/issues) ![](https://img.shields.io/badge/runs%20on-laravel%206.x-red) [![bc1qres8wt48dl8wkyfhug9lyj9mw89tgl8vslndmg](https://img.shields.io/badge/donate-bitcoin-orange)](bitcoin:bc1qres8wt48dl8wkyfhug9lyj9mw89tgl8vslndmg)

A simple audit trail recorder library for Laravel.

# Table of Contents

* [Installation](https://github.com/liamdemafelix/auditor#installation)
* [Accessing the Audit Trail](https://github.com/liamdemafelix/auditor#accessing-the-audit-trail)
  * [What does it look like?](https://github.com/liamdemafelix/auditor#what-does-it-look-like)
* [Discarding Data](https://github.com/liamdemafelix/auditor#discarding-data)
* [License](https://github.com/liamdemafelix/auditor#license)

# Installation

Install the package via composer:

```bash
composer require demafelix/laravel-auditor "^v1.0"
```

This will include the auditor package in your project. Now, publish the configuration file and database migration:

```bash
php artisan vendor:publish --provider=Demafelix\Auditor\Providers\AuditorServiceProvider
```

Next, migrate the newly-published migration file:

```bash
php artisan migrate
```

Finally, edit `config/auditor.php` and add the models you want to enable logging for:

```php
<?php

return [
    /**
     * Specify the models to watch by providing their
     * fully-qualified class names below.
     *
     * @var array
     */

    'models' => [
        'App\User', 'App\Product'
    ]
];
```

And you're done.

# Accessing the Audit Trail

Audit trail records are saved in the `audit_trails` table and is automatically created upon every successful `created`, `updated` and `deleted` event monitored by an observer. Records are stored in JSON and can be searched via fuzzy search (using `LIKE` direct in the `record` column), or by using Laravel's [`whereJsonContains()`](https://laravel.com/docs/6.x/queries#json-where-clauses) method for more specific results.

## What does it look like?

The actual record is stored as JSON, so it's easy to do a `json_decode()` on the record and call whatever record you want to use. For example:

```php
<?php

// ... other code here ... //

$result = json_decode($trail->record);
$oldFirstName = $result->first_name->old;
$newFirstName = $result->first_name->new;
```

Here's a sample of what gets recorded in the `record` column for a `created` action:

```json
{
   "first_name":{
      "old":null,
      "new":"Liam"
   },
   "last_name":{
      "old":null,
      "new":"Demafelix"
   },
   "email":{
      "old":null,
      "new":"liamdemafelix.n@gmail.com"
   },
   "mobile":{
      "old":null,
      "new":"09560760282"
   },
   "role":{
      "old":null,
      "new":"2"
   },
   "updated_at":{
      "old":null,
      "new":"2019-09-29 06:45:06"
   },
   "created_at":{
      "old":null,
      "new":"2019-09-29 06:45:06"
   },
   "user_id":{
      "old":null,
      "new":1
   }
}
```

On update, it only saves the fields that actually changed (and because we're using Observers, calling `update()` with the same data, it won't record a new entry):

```json
{
   "first_name":{
      "old":"Liam",
      "new":"Test"
   },
   "last_name":{
      "old":"Demafelix",
      "new":"Change"
   },
   "updated_at":{
      "old":"2019-09-29 06:46:11",
      "new":"2019-09-29 06:46:14"
   }
}
```

# Discarding Data

Because your audit trail records are stored in plaintext, you should **never** save sensitive data in the audit trail. To exclude a field from being stored in the audit trail, add a `$discarded` array in your model:

```php
<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The keys defined in this array is discared by the auditor.
     *
     * @var array
     */
    public $discarded = [
        'password'
    ];
}
```

If `$discarded` is not defined in your model, **all attributes get saved in the audit trail**. Make sure you add the necessary adjustments to your model.

# License

This library is published under the [MIT Open Source license](https://github.com/liamdemafelix/auditor/blob/master/LICENSE).
