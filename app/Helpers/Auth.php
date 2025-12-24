<?php
namespace App\Helpers;

use App\Services\AuthService;

class Auth {
    
    public static function logoutAndRedirect(): void {
        Session::destroy();
        Redirect::to('/login');
    }

    public static function requireLogin(): void {
        if(!Session::has('userId')) {
            self::logoutAndRedirect();
        }
        $userId = (int) Session::get('userId');

        $authService = new AuthService();
        $result = $authService->refreshUserSession($userId);

        if(!$result['success']) {
            self::logoutAndRedirect();
        }
    }
    
    public static function redirectIfLoggedIn(): void {
        if(Session::has('userId')) {
            Redirect::to('/dashboard');
        }
    }

}