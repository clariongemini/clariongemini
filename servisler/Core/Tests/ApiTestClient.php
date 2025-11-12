<?php
// Bu sınıfın global bir ad alanında olduğunu veya otomatik olarak yüklendiğini varsayıyoruz.

class ApiTestClient
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function post(string $uri, array $body): object
    {
        // Bu basit mock, sadece LoginApiTest'in ihtiyacını karşılar.
        // Gerçek bir uygulamada, URI'yi ayrıştırıp doğru denetleyiciyi bulması gerekir.

        $authController = new \ProSiparis\Auth\Controllers\AuthController($this->pdo); // Varsayımsal AuthController
        $response = $authController->login($body); // Varsayımsal login metodu

        // Yanıtı basit bir nesne olarak döndür
        return new class($response) {
            private $response;
            public function __construct($response) { $this->response = $response; }
            public function getStatusCode() { return $this->response['code']; }
            public function getBody() { return json_encode($this->response['data']); }
        };
    }

    public function put(string $uri, array $body, array $headers): object
    {
        // Bu basit mock, sadece SiparisDurumGuncellemeTest'in ihtiyacını karşılar.

        // URI'den ID'yi al
        preg_match('/\/(\d+)\//', $uri, $matches);
        $siparisId = $matches[1];

        $_SERVER['HTTP_X_USER_ID'] = $headers['X-User-ID'];
        $_POST['durum'] = json_encode($body['durum']);

        $siparisController = new \ProSiparis\Siparis\Controllers\SiparisController($this->pdo); // Varsayımsal SiparisController
        $response = $siparisController->durumGuncelle($siparisId);

        return new class($response) {
            private $response;
            public function __construct($response) { $this->response = $response; }
            public function getStatusCode() { return $this->response['code']; }
        };
    }
}
