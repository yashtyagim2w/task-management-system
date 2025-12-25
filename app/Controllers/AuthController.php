<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\Auth;
use App\Helpers\Logger;
use App\Helpers\Redirect;
use App\Helpers\Session;
use App\Services\AuthService;
use Throwable;

class AuthController extends Controller {

    public function renderLoginForm(): void {
        Auth::redirectIfLoggedIn();

        $sessionFlashMessageKey = 'login_form';
        $data = [
            'header_title' => 'Login - Task Management System',
            'public_page' => true,
        ];

        if(Session::has($sessionFlashMessageKey)) {
            $data['flash_message'] = Session::get($sessionFlashMessageKey);
            Session::remove($sessionFlashMessageKey);
        }
        $this->render('/auth/login', $data);
    }

    public function login(): void {
        Auth::redirectIfLoggedIn();

        $redirectPath = '/login';
        $sessionFlashMessageKey = 'login_form';

        $flashMessage = [
            'message' => '',
            'isError' => true,
        ];

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Redirect::to($redirectPath);
        }
        
        $email = htmlspecialchars(trim($_POST['email']));
        $password = $_POST['password'];
        
        if(empty($email) || empty($password)) {
            $flashMessage['message'] = 'Email and Password are required.';
        } elseif (strlen($email) < 5 || strlen($email) > 128) {
            $flashMessage['message'] = 'Email must be between 5 and 128 characters.';
        } elseif (strlen($password) < 6 || strlen($password) > 32) {
            $flashMessage['message'] = 'Password must be between 6 and 32 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flashMessage['message'] = 'Please enter a valid email address.';
        }

        if(strlen($flashMessage['message']) > 1) {
            Redirect::redirectWithMessage($redirectPath, $sessionFlashMessageKey, $flashMessage);
        }

        try {
            $authService = new AuthService();
            $result = $authService->login($email, $password);
            if(!$result['success']) {
                $flashMessage['message'] = $result['message'];
                Redirect::redirectWithMessage($redirectPath, $sessionFlashMessageKey, $flashMessage);
            }

            // set session
            $userData = $result['data'];
            Session::set('userId', $userData['id']);
            Session::set('firstName', $userData['firstName']);
            Session::set('lastName', $userData['lastName']);
            Session::set('email', $userData['email']);
            Session::set('roleId', $userData['roleId']);
            Session::set('roleName', $userData['roleName']);

            // regenerate session id to prevent session fixation
            Session::regenerate();
            $redirectRole = $userData['roleName'] === 'super_admin' ? 'admin' : $userData['roleName'];
            Redirect::to("/{$redirectRole}/dashboard");

        } catch (Throwable $e) {
            Logger::error($e);
            $flashMessage['message'] = "Internal Server Error.";
            Redirect::redirectWithMessage($redirectPath, $sessionFlashMessageKey, $flashMessage);
        }
    }

    public function logout(): void {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::logoutAndRedirect();
        }
    }

}