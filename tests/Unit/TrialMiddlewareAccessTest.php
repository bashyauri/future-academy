<?php

use App\Http\Middleware\EnsureSubscriptionOrTrial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

uses(TestCase::class);

it('allows trial users to access free-content routes through the middleware', function () {
    $user = new class
    {
        public function hasAnyRole(array $roles): bool
        {
            return false;
        }

        public function isParent(): bool
        {
            return false;
        }

        public function onTrial(): bool
        {
            return true;
        }

        public function hasActiveSubscription(): bool
        {
            return false;
        }
    };

    Auth::shouldReceive('user')->once()->andReturn($user);

    $request = Request::create('/lessons', 'GET');
    $route = new Route('GET', '/lessons', ['as' => 'lessons.subjects', function () {
        return 'ok';
    }]);
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    $response = app(EnsureSubscriptionOrTrial::class)->handle(
        $request,
        fn () => new Response('ok', 200),
    );

    expect($response->getStatusCode())->toBe(200);
});

it('redirects trial users away from premium routes in the middleware', function () {
    $user = new class
    {
        public function hasAnyRole(array $roles): bool
        {
            return false;
        }

        public function isParent(): bool
        {
            return false;
        }

        public function onTrial(): bool
        {
            return true;
        }

        public function hasActiveSubscription(): bool
        {
            return false;
        }
    };

    Auth::shouldReceive('user')->once()->andReturn($user);

    $request = Request::create('/mock', 'GET');
    $route = new Route('GET', '/mock', ['as' => 'mock.setup', function () {
        return 'ok';
    }]);
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    $response = app(EnsureSubscriptionOrTrial::class)->handle(
        $request,
        fn () => new Response('ok', 200),
    );

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('payment.pricing'));
});

it('allows guardians to access enrollment setup routes without entitlement', function () {
    $user = new class
    {
        public function hasAnyRole(array $roles): bool
        {
            return false;
        }

        public function isParent(): bool
        {
            return true;
        }

        public function children(): object
        {
            return new class
            {
                public function wherePivot(string $column, bool $value): self
                {
                    return $this;
                }

                public function pluck(string $column): Collection
                {
                    return collect([10]);
                }
            };
        }
    };

    Auth::shouldReceive('user')->once()->andReturn($user);

    $request = Request::create('/lessons', 'GET');
    $route = new Route('GET', '/lessons', ['as' => 'lessons.subjects', function () {
        return 'ok';
    }]);
    $route->bind($request);
    $request->setRouteResolver(fn () => $route);

    $response = app(EnsureSubscriptionOrTrial::class)->handle(
        $request,
        fn () => new Response('ok', 200),
    );

    expect($response->getStatusCode())->toBe(200);
});
