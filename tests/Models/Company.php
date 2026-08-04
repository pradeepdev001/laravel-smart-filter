<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pradeepdev\SmartFilter\Traits\Filterable;

class Company extends Model
{
    use Filterable;

    protected $table = 'companies';

    protected $fillable = ['name', 'city'];

    protected array $filterable = ['name', 'city'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
