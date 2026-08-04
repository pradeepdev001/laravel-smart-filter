<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Tests\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];

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
    ];

    protected array $searchable = ['name', 'email'];

    protected array $filterAliases = ['active' => 'is_active'];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
