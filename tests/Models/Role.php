<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Pradeepdev\SmartFilter\Traits\Filterable;

class Role extends Model
{
    use Filterable;

    protected $table = 'roles';

    protected $fillable = ['name'];

    protected array $filterable = ['name'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
