<?php
namespace ProSiparis\Auth\Service;

class AuthService
{
    public function parolaDogrula(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }
}
