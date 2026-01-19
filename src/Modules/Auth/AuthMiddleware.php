<?php

namespace ITSS\Modules\Auth;

use ITSS\Core\Request;
use ITSS\Core\Response;
use ITSS\Core\Session;

class AuthMiddleware
{
    public static function handle(Request $request, Response $response): bool
    {
        $publicPaths = ['/', '/auth/login', '/auth/callback'];
        $currentPath = parse_url($request->uri(), PHP_URL_PATH);

        if (in_array($currentPath, $publicPaths)) {
            return true;
        }

        $userId = Session::get('user_id');

        if (!$userId) {
            if ($request->header('Accept') === 'application/json' ||
                str_starts_with($currentPath, '/api/')) {
                $response->status(401)->json(['error' => 'Unauthorized']);
                return false;
            }

            $response->redirect('/auth/login');
            return false;
        }

        return true;
    }

    public static function requireRole(string ...$roles): callable
    {
        return function(Request $request, Response $response) use ($roles): bool {
            $userRole = Session::get('user_role');

            if (!in_array($userRole, $roles)) {
                if ($request->header('Accept') === 'application/json') {
                    $response->status(403)->json(['error' => 'Forbidden']);
                } else {
                    $response->status(403)->html('Access denied');
                }
                return false;
            }

            return true;
        };
    }
}
