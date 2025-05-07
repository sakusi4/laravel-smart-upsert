<?php

use Illuminate\Database\Eloquent\Model;
use Sakusi4\LaravelSmartUpsert\SmartUpsertBuilder;

class User extends Model
{
    protected $guarded = [];
    public $timestamps = false;
    protected $table   = 'users';
    protected $fillable = ['email', 'name'];
}

it('upserts only dirty rows', function () {
    User::create(['email' => 'a@test.com', 'name' => 'Old']);

    SmartUpsertBuilder::upsert(
        User::class,
        [
            ['email' => 'a@test.com', 'name' => 'New'],
            ['email' => 'b@test.com', 'name' => 'BrandNew'],
        ],
        'email',
        ['name']
    );

    dump('test');

    expect(User::count())->toBe(2);
    expect(User::where('email','a@test.com')->value('name'))->toBe('New');
    expect(User::where('email','b@test.com')->value('name'))->toBe('BrandNew');
});