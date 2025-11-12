<?php
namespace ProSiparis\Auth\Tests\Unit;

use ProSiparis\Auth\Tests\TestCase;
// Bu servisin var olduğunu ve parolaDogrula metoduna sahip olduğunu varsayıyoruz.
use ProSiparis\Auth\Service\AuthService;

class PasswordHashingTest extends TestCase
{
    /** @test */
    public function a_correct_password_should_be_verified_successfully()
    {
        // Bu test, AuthService'in veritabanı gibi dış bağımlılıkları olmadığını varsayar.
        // Eğer varsa, bu bağımlılıkları "mock"lamamız gerekirdi.
        $authService = new AuthService();

        $plainPassword = 'password123';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $this->assertTrue(
            $authService->parolaDogrula($plainPassword, $hashedPassword),
            "Doğru parola doğrulanmalıdır."
        );
    }

    /** @test */
    public function an_incorrect_password_should_fail_verification()
    {
        $authService = new AuthService();

        $plainPassword = 'password123';
        $incorrectPassword = 'wrongpassword';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $this->assertFalse(
            $authService->parolaDogrula($incorrectPassword, $hashedPassword),
            "Yanlış parola doğrulanmamalıdır."
        );
    }
}
