<?php

class User extends Model
{
    protected $table = 'users';

    public function authenticate($email, $password)
    {
        $user = $this->getOneWhere(['email' => $email, 'attivo' => 1]);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function register($data)
    {
        if ($this->emailExists($data['email'])) {
            return false;
        }
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->insert($data);
    }

    public function emailExists($email)
    {
        $result = $this->getOneWhere(['email' => $email]);
        return !empty($result);
    }

    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }
}
