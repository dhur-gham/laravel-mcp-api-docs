<?php

use DhurGham\LaravelMcpApiDocs\Http\Middleware\CatchMcpAuthExceptions;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('returns 401 JSON when AuthenticationException is thrown', function () {
    $middleware = new CatchMcpAuthExceptions;
    $request = Request::create('/mcp/api-docs', 'GET');

    $response = $middleware->handle($request, fn () => throw new AuthenticationException);

    expect($response->getStatusCode())->toBe(401);
    $data = $response->getData(true);
    expect($data['error'])->toBe('Unauthenticated')
        ->and($data['message'])->toBe('Invalid or missing token.');
});

it('returns 403 JSON when AuthorizationException is thrown', function () {
    $middleware = new CatchMcpAuthExceptions;
    $request = Request::create('/mcp/api-docs', 'GET');

    $response = $middleware->handle($request, fn () => throw new AuthorizationException);

    expect($response->getStatusCode())->toBe(403);
    $data = $response->getData(true);
    expect($data['error'])->toBe('Forbidden')
        ->and($data['message'])->toBe('You do not have permission to use this resource.');
});

it('returns 403 JSON when AccessDeniedHttpException is thrown', function () {
    $middleware = new CatchMcpAuthExceptions;
    $request = Request::create('/mcp/api-docs', 'GET');

    $response = $middleware->handle($request, fn () => throw new AccessDeniedHttpException);

    expect($response->getStatusCode())->toBe(403);
    $data = $response->getData(true);
    expect($data['error'])->toBe('Forbidden');
});

it('returns 401 JSON when HttpException 401 is thrown', function () {
    $middleware = new CatchMcpAuthExceptions;
    $request = Request::create('/mcp/api-docs', 'GET');

    $response = $middleware->handle($request, fn () => throw new HttpException(401));

    expect($response->getStatusCode())->toBe(401);
    $data = $response->getData(true);
    expect($data['error'])->toBe('Unauthenticated');
});

it('passes through when next returns successfully', function () {
    $middleware = new CatchMcpAuthExceptions;
    $request = Request::create('/mcp/api-docs', 'GET');
    $expected = response()->json(['ok' => true], 200);

    $response = $middleware->handle($request, fn () => $expected);

    expect($response)->toBe($expected);
});
