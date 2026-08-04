<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pradeepdev\SmartFilter\Traits\Filterable;

class Post extends Model
{
    use Filterable;

    protected $table = 'posts';

    protected $fillable = ['user_id', 'title', 'body', 'status'];

    protected array $filterable = ['title', 'status', 'user_id'];

    protected array $searchable = ['title', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
