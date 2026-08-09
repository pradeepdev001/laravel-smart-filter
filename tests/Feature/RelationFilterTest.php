<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Pradeepdev\SmartFilter\Tests\Models\Company;
use Pradeepdev\SmartFilter\Tests\Models\Post;
use Pradeepdev\SmartFilter\Tests\Models\Role;
use Pradeepdev\SmartFilter\Tests\Models\User;

// ---------------------------------------------------------------------------
// whereHas — basic relation filtering
// ---------------------------------------------------------------------------

it('filters users who have posts with a specific status', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com']);

    Post::create(['user_id' => $alice->id, 'title' => 'Post A', 'status' => 'published']);
    Post::create(['user_id' => $bob->id,   'title' => 'Post B', 'status' => 'draft']);

    $request = Request::create('/users', 'GET', ['posts.status' => 'published']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('filters users who have posts with LIKE on title', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com']);

    Post::create(['user_id' => $alice->id, 'title' => 'Laravel Tips',   'status' => 'published']);
    Post::create(['user_id' => $bob->id,   'title' => 'PHP Basics',     'status' => 'published']);

    $request = Request::create('/users', 'GET', ['posts.title~' => 'laravel']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('filters users who have posts with NOT EQUALS on status', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com']);

    Post::create(['user_id' => $alice->id, 'title' => 'Post A', 'status' => 'published']);
    Post::create(['user_id' => $bob->id,   'title' => 'Post B', 'status' => 'draft']);

    $request = Request::create('/users', 'GET', ['posts.status!=' => 'draft']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('filters users who have posts with IN operator on status', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com']);
    $carol = User::create(['name' => 'Carol', 'email' => 'carol@example.com']);

    Post::create(['user_id' => $alice->id, 'title' => 'A', 'status' => 'published']);
    Post::create(['user_id' => $bob->id,   'title' => 'B', 'status' => 'archived']);
    Post::create(['user_id' => $carol->id, 'title' => 'C', 'status' => 'draft']);

    $request = Request::create('/users', 'GET', ['posts.status' => 'in(published,archived)']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Bob');
});

// ---------------------------------------------------------------------------
// has() / doesntHave() — existence checks
// ---------------------------------------------------------------------------

it('filters users who have at least one post using has', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com']);

    Post::create(['user_id' => $alice->id, 'title' => 'Post A', 'status' => 'published']);
    // Bob has no posts

    $request = Request::create('/users', 'GET', ['posts' => 'has']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('filters users who have no posts using doesntHave', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com']);

    Post::create(['user_id' => $alice->id, 'title' => 'Post A', 'status' => 'published']);

    $request = Request::create('/users', 'GET', ['posts' => 'doesntHave']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Bob');
});

// ---------------------------------------------------------------------------
// BelongsToMany — roles
// ---------------------------------------------------------------------------

it('filters users who have a specific role', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com']);

    $admin = Role::create(['name' => 'admin']);
    $editor = Role::create(['name' => 'editor']);

    $alice->roles()->attach($admin->id);
    $bob->roles()->attach($editor->id);

    $request = Request::create('/users', 'GET', ['roles.name' => 'admin']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('filters users with roles using IN operator', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com']);
    $carol = User::create(['name' => 'Carol', 'email' => 'carol@example.com']);

    $admin = Role::create(['name' => 'admin']);
    $editor = Role::create(['name' => 'editor']);
    $viewer = Role::create(['name' => 'viewer']);

    $alice->roles()->attach($admin->id);
    $bob->roles()->attach($editor->id);
    $carol->roles()->attach($viewer->id);

    $request = Request::create('/users', 'GET', ['roles.name' => 'in(admin,editor)']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Bob');
});

// ---------------------------------------------------------------------------
// Nested relations — BelongsTo chain
// ---------------------------------------------------------------------------

it('filters users by their company name (nested BelongsTo)', function (): void {
    $acme = Company::create(['name' => 'Acme Corp', 'city' => 'New York']);
    $globo = Company::create(['name' => 'Globo Inc', 'city' => 'London']);

    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'company_id' => $acme->id]);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'company_id' => $globo->id]);

    $request = Request::create('/users', 'GET', ['company.name' => 'Acme Corp']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});

it('filters users by company city using LIKE', function (): void {
    $acme = Company::create(['name' => 'Acme Corp', 'city' => 'New York']);
    $globo = Company::create(['name' => 'Globo Inc', 'city' => 'New Delhi']);
    $local = Company::create(['name' => 'Local Ltd', 'city' => 'London']);

    User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'company_id' => $acme->id]);
    User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'company_id' => $globo->id]);
    User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'company_id' => $local->id]);

    $request = Request::create('/users', 'GET', ['company.city~' => 'New']);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Bob');
});

// ---------------------------------------------------------------------------
// Combining relation filters with flat filters
// ---------------------------------------------------------------------------

it('combines relation filter with a flat filter', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'status' => 'active']);
    $carol = User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'status' => 'inactive']);

    Post::create(['user_id' => $alice->id, 'title' => 'Post A', 'status' => 'published']);
    Post::create(['user_id' => $bob->id,   'title' => 'Post B', 'status' => 'published']);
    // Carol has no posts

    $request = Request::create('/users', 'GET', [
        'status' => 'active',
        'posts.status' => 'published',
    ]);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('Alice', 'Bob');
});

it('combines has check with flat filter', function (): void {
    $alice = User::create(['name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active']);
    $bob = User::create(['name' => 'Bob',   'email' => 'bob@example.com',   'status' => 'active']);
    $carol = User::create(['name' => 'Carol', 'email' => 'carol@example.com', 'status' => 'inactive']);

    Post::create(['user_id' => $alice->id, 'title' => 'Post A', 'status' => 'published']);
    // Bob and Carol have no posts

    $request = Request::create('/users', 'GET', [
        'status' => 'active',
        'posts' => 'has',
    ]);
    $results = User::smartFilter($request)->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Alice');
});
