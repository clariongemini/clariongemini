<?php
namespace ProSiparis\Auth\Tests\Integration;

use ProSiparis\Auth\Tests\TestCase;
// GuzzleHttp veya benzeri bir HTTP istemcisinin 'composer require --dev guzzlehttp/guzzle' ile eklendiğini varsayıyoruz.
use GuzzleHttp\Client;

class LoginApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Test için sahte bir kullanıcı oluştur
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO kullanicilar (rol_id, ad_soyad, eposta, parola) VALUES (2, 'Test User', 'test@example.com', ?)");
        $stmt->execute([$password]);
    }

    /** @test */
    public function it_returns_401_for_incorrect_credentials()
    {
        // Bu test, API'nin çalışır durumda olduğunu ve yönlendirilebildiğini varsayar.
        // Gerçek bir projede, bu isteği yapmak için bir web sunucusu (Nginx, Apache) veya
        // PHP'nin dahili sunucusu gerekir. Test ortamında bu yönlendirmeyi simüle ettiğimizi varsayıyoruz.

        // Varsayımsal bir API istemcisi
        $client = new ApiTestClient($this->pdo);

        $response = $client->post('/api/kullanici/giris', [
            'eposta' => 'test@example.com',
            'parola' => 'wrongpassword'
        ]);

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function it_returns_a_jwt_token_for_correct_credentials()
    {
        $client = new ApiTestClient($this->pdo);

        $response = $client->post('/api/kullanici/giris', [
            'eposta' => 'test@example.com',
            'parola' => 'password123'
        ]);

        $data = json_decode($response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('token', $data);
    }
}

// Not: ApiTestClient, gerçek bir HTTP isteği yapmak yerine, isteği doğrudan
// yönlendiriciye (router) ve denetleyiciye (controller) ileten varsayımsal bir yardımcı sınıftır.
// Bu, testleri daha hızlı ve web sunucusu bağımlılığı olmadan çalıştırmayı sağlar.
