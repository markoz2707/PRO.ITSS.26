<?php

namespace ITSS\Modules\Auth;

use ITSS\Core\Request;
use ITSS\Core\Response;
use ITSS\Core\Session;
use ITSS\Core\Logger;

class AuthController
{
    private AzureADService $azureService;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->azureService = new AzureADService($config['azure']);
    }

    public function login(Request $request, Response $response): void
    {
        $authUrl = $this->azureService->getAuthorizationUrl();
        Session::set('oauth2_state', $this->azureService->getState());
        $response->redirect($authUrl);
    }

    public function callback(Request $request, Response $response): void
    {
        $code = $request->query('code');
        $state = $request->query('state');
        $sessionState = Session::get('oauth2_state');

        if (empty($code)) {
            Logger::warning('OAuth callback missing code');
            $response->redirect('/?error=missing_code');
            return;
        }

        if (empty($state) || $state !== $sessionState) {
            Logger::warning('OAuth state mismatch', [
                'received' => $state,
                'expected' => $sessionState
            ]);
            $response->redirect('/?error=invalid_state');
            return;
        }

        try {
            $accessToken = $this->azureService->getAccessToken($code);
            $userInfo = $this->azureService->getUserInfo($accessToken);

            $userModel = new \ITSS\Models\User();
            $user = $userModel->findByEmail($userInfo['email']);

            if (!$user) {
                $userId = $userModel->createFromAzure($userInfo);
                $user = $userModel->findById($userId);
            } else {
                $userModel->updateAzureInfo($user['id'], $userInfo);
                $user = $userModel->findById($user['id']);
            }

            if (!$user['is_active']) {
                Logger::warning('Inactive user attempted login', ['email' => $user['email']]);
                $response->redirect('/?error=account_inactive');
                return;
            }

            Session::set('user_id', $user['id']);
            Session::set('user_email', $user['email']);
            Session::set('user_name', $user['first_name'] . ' ' . $user['last_name']);
            Session::set('user_role', $user['role']);
            Session::set('access_token', $accessToken);
            Session::regenerate();

            Logger::info('User logged in', ['user_id' => $user['id'], 'email' => $user['email']]);

            $response->redirect('/dashboard');
        } catch (\Exception $e) {
            Logger::error('OAuth callback error: ' . $e->getMessage());
            $response->redirect('/?error=auth_failed');
        }
    }

    public function logout(Request $request, Response $response): void
    {
        $userId = Session::get('user_id');
        Logger::info('User logged out', ['user_id' => $userId]);

        Session::destroy();
        $response->redirect('/');
    }

    public function check(Request $request, Response $response): void
    {
        $userId = Session::get('user_id');

        if ($userId) {
            $userModel = new \ITSS\Models\User();
            $user = $userModel->findById($userId);

            if ($user && $user['is_active']) {
                $response->json([
                    'authenticated' => true,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'name' => $user['first_name'] . ' ' . $user['last_name'],
                        'role' => $user['role']
                    ]
                ]);
                return;
            }
        }

        $response->json(['authenticated' => false]);
    }
}
