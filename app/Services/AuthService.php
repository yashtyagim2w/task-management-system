<?php
namespace App\Services;

use App\Core\Service;
use App\Helpers\Session;
use App\Models\Users;

class AuthService extends Service {

    protected Users $usersModel;

    public function __construct() {
        parent::__construct();
        $this->usersModel = new Users();
    }

    public function login(string $email, string $password): array {
        $user = $this->usersModel->getByEmail($email)[0];

        // check if user exist and verify password
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failure("Invalid email or password");
        }

        // check if user is active
        if (!$user['is_active']) {
            return $this->failure("User account is inactive. Please contact support.");
        }

        // user data
        $userData = [
            'id' => $user['id'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'email' => $user['email'],
            'roleId' => $user['role_id'],
            'roleName' => $user['role_name'],
        ];

        return $this->success("Login successful", $userData);
    }

    public function refreshUserSession(int $userId): array {
        $user = $this->usersModel->getUserDetailsById($userId)[0];

        if (!$user) {
            return $this->failure("User not found");
        }

        if(!$user['is_active']) {
            return $this->failure("User account is inactive. Please contact support.");
        }

        $userData = [
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'email' => $user['email'],
            'roleId' => $user['role_id'],
            'roleName' => $user['role_name'],
        ];

        // update session data
        Session::set('firstName', $userData['firstName']);
        Session::set('lastName', $userData['lastName']);
        Session::set('email', $userData['email']);
        Session::set('roleId', $userData['roleId']);
        Session::set('roleName', $userData['roleName']);

        return $this->success("User session refreshed", $userData);
    }
}