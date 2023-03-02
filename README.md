> This repository and package has been deprecated. Use at your own risk.

# demafelix/laravel-auditor

[![GitHub issues](https://img.shields.io/github/issues/liamdemafelix/laravel-auditor)](https://github.com/liamdemafelix/laravel-auditor/issues) ![](https://img.shields.io/badge/runs%20on-laravel%206.x%20%20to%208.x-red)

**Throw me some sats**: [3AGeUPgJfQXGeBxp8jswVprJ791gVdkqDR](bitcoin:3AGeUPgJfQXGeBxp8jswVprJ791gVdkqDR)

`demafelix/laravel-auditor` is a simple model audit trail recorder for Laravel.

# Pre-requisites

* Laravel 6.x - 8.x
* MySQL/MariaDB versions that support the `json()` data type equivalent for Laravel (see [https://laravel.com/docs/6.x/migrations#creating-columns](https://laravel.com/docs/6.x/migrations#creating-columns))
    * Older versions may work by changing `json()` to `longText()`, see note in the Installation Instructions below.

# Installation

Install the package via composer:

```bash
composer require demafelix/laravel-auditor
```

This will include the auditor package in your project. Now, publish the configuration file and database migration:

```bash
php artisan vendor:publish --provider=Demafelix\Auditor\Providers\AuditorServiceProvider
```

Next, migrate the newly-published migration file:

```bash
php artisan migrate
```

> If migrations fail due to an old version of MySQL/MariaDB, change the migration to use `longText()` instead of `json()` instead.

### Hold up, I use a UUID/string instead of an integer for primary keys!

Well, in most cases, that's a [bad idea](https://tomharrisonjr.com/uuid-or-guid-as-primary-keys-be-careful-7b2aa3dcb439). Nevertheless, you may update the data type of the `user_id` field to match your primary key data type. No other change needs to be done, as the logs are stored in JSON.

# Configuration

Upon publishing the vendor files (using `php artisan vendor:publish` above), a file named `/config/auditor.php` will be created. Inside, you can edit the following settings:

* `models` - An array of models to watch for Eloquent operations for logging.
* `global_discards` - An array of fields to exclude from logs **globally**. By default, these are:
    * `password`
    * `remember_token`
    * `created_at`
    * `updated_at`
    * `deleted_at`
    * `banned_at`
        * You may add and delete values in this array to your liking, but we already save the timestamps for the operation so it's pointless to save them in the actual log.
        * **Never** save sensitive information in plaintext. Sane defaults have been provided, adjust as necessary.

# Records

Audit trail records are saved in the `audit_trails` table and is automatically created upon every successful `created`, `updated` and `deleted` event monitored by an observer. Records are stored in JSON and can be searched via fuzzy search (using `LIKE` direct in the `record` column), or by using Laravel's [`whereJsonContains()`](https://laravel.com/docs/6.x/queries#json-where-clauses) method for more specific results.

### What does it look like?

The actual record is stored as JSON, so it's easy to do a `json_decode()` on the record and call whatever record you want to use. For example:

```php
<?php

// ... other code here ... //

$result = json_decode($trail->record);
echo "Old value: " . $result->name->old . "<br>";
echo "New value: " . $result->name->new;
```

> On update, it only saves the fields that actually changed (and because we're using Observers, calling `update()` with the same data won't record a new entry)

It's clean and coherent, you can modify your spiels to look however you want, since we only store the data and not how it's constructed. In JSON, it looks like the following (an example of a `create` action log):

```json
{ 
   "name":{ 
      "old": "John Smith",
      "new": "Mario Berge"
   },
   "email":{ 
      "old": "john.smith@example.com",
      "new": "dbergstrom@stokes.biz"
   }
}
```

# Discarding Data

### Global Discards

You can discard a field name globally by setting it in `/config/auditor.php`.

```php
<?php

return [
    /**
     * Specify fields to discard.
     * The fields specified in this configuration are discarded for all models.
     * To make model-specific discards, use the $discarded declaration on your model.
     *
     * @var array
     */

    'global_discards' => [
        'password', 'remember_token', 'created_at', 'updated_at', 'deleted_at', 'banned_at'
    ]
];
```

### Model-specific Discards

In addition, if you want to discard a field specific to a model, you may add a `public $discarded` declaration in your model:

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

**Never** store sensitive data in plaintext. Sane defaults have been provided (see `/config/auditor.php`), adjust as necessary.

# License

This library is published under the [MIT Open Source license](https://github.com/liamdemafelix/auditor/blob/master/LICENSE).
