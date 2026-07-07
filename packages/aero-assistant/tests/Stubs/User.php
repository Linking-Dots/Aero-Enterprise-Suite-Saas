<?php

declare(strict_types=1);

namespace Aero\Assistant\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Minimal authenticatable for endpoint tests. `id` is set via attributes so
 * auth()->id() resolves without a users table.
 */
class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}
