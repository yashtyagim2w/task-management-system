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
            $role = Session::get('roleName') === 'super_admin' ? 'admin' : Session::get('roleName');
            Redirect::to("/{$role}/dashboard");
        }
    }

    public static function isLoggedIn(): bool {
        return Session::has('userId');
    }

    public static function adminOnly(): void {
        if(Session::get('roleName') !== 'super_admin') {
            Redirect::to('/unauthorized');
        }
    }

    public static function managerOnly(): void {
        if(Session::get('roleName') !== 'manager') {
            Redirect::to('/unauthorized');
        }
    }

    public static function employeeOnly(): void {
        if(Session::get('roleName') !== 'employee') {
            Redirect::to('/unauthorized');
        }
    }

    public static function managerOrAdminOnly(): void {
        $role = Session::get('roleName');
        if($role !== 'super_admin' && $role !== 'manager') {
            Redirect::to('/unauthorized');
        }
    }

}