<?php

namespace Echto\Laravel\Facades;

use Echto\Laravel\Testing\FakeClient;
use Echto\Laravel\Verdict;
use Echto\Laravel\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Verification check(Request $request, string $form, string $action = 'submit')
 * @method static void confirmUsing(string $form, \Closure $response)
 * @method static FakeClient fake(Verdict|string $verdict = Verdict::Pass)
 *
 * @see \Echto\Laravel\Echto
 */
class Echto extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Echto\Laravel\Echto::class;
    }
}
