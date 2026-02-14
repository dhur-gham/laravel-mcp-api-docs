<?php

namespace DhurGham\LaravelMcpApiDocs\Http\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CatchMcpAuthExceptions
{
    public function handle(Request $request, \Closure $next): mixed
    {
        try {
            return $next($request);
        } catch (AuthenticationException $e) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Invalid or missing token.',
            ], 401);
        } catch (AuthorizationException|AccessDeniedHttpException $e) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You do not have permission to use this resource.',
            ], 403);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 401) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'Invalid or missing token.',
                ], 401);
            }
            if ($e->getStatusCode() === 403) {
                return response()->json([
                    'error' => 'Forbidden',
                    'message' => 'You do not have permission to use this resource.',
                ], 403);
            }
            throw $e;
        }
    }
}
