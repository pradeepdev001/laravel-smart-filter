<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pradeepdev\SmartFilter\Traits\Filterable;

class User extends Model
{
    use Filterable;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'status',
        'age',
        'price',
        'country',
        'is_active',
        'deleted_at',
        'company_id',
    ];

    /** @var list<string> */
    protected array $filterable = [
        'name',
        'email',
        'status',
        'age',
        'price',
        'country',
        'is_active',
        'deleted_at',
        'created_at',
        'company_id',
    ];

    /** @var list<string> */
    protected array $searchable = ['name', 'email'];

    /** @var array<string, string> */
    protected array $filterAliases = ['active' => 'is_active'];

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
}
