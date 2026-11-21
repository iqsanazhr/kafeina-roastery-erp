<?php

declare(strict_types=1);

test('app does not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

test('models extend eloquent base model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring('App\Models\User'); // User extends Authenticatable which extends Model

test('controllers extend base controller')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller');

test('services are classes and reside in App\Services namespace')
    ->expect('App\Services')
    ->toBeClasses();

test('enums are backed enums or valid enums')
    ->expect('App\Enums')
    ->toBeEnums();

test('app does not call env directly to prevent config cache issues')
    ->expect('env')
    ->not->toBeUsedIn('App');
