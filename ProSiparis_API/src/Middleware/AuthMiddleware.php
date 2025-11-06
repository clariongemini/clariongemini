<?php
namespace ProSiparis\Middleware;

use ProSiparis\Core\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;

class AuthMiddleware
{
    /**
     * Gelen isteği işler ve JWT doğrulaması yapar.
     * @param Request $request
     */
    public function handle(Request $request): void
    {
        $jwt = $request->getBearerToken();

        if (!$jwt) {
            $this->sendUnauthorized('Kimlik doğrulama token\'ı bulunamadı.');
        }

        try {
            $decoded = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));

            // Çözümlenmiş kullanıcı verisini, isteğe daha sonra erişilebilmesi için ekleyelim.
            // Bu, normalde daha gelişmiş bir Request nesnesi veya bir dependency injection container ile yapılır.
            // Şimdilik, global bir değişkende veya statik bir özellikte saklayabiliriz.
            // Basitlik adına, şimdilik bu veriyi bir sonraki katmanın doğrudan kullanacağını varsayalım.
            // Gerçek bir uygulamada, bu veri Request nesnesine eklenirdi: $request->setUser($decoded->data);

            // Router'a kullanıcının kim olduğunu bildirmek için bir yöntem...
            // Bu basit yapı için, session gibi davranan statik bir property kullanabiliriz.
            \ProSiparis\Core\Auth::setUser($decoded->data);

        } catch (ExpiredException $e) {
            $this->sendUnauthorized('Oturum süreniz doldu. Lütfen tekrar giriş yapın.');
        } catch (Exception $e) {
            $this->sendUnauthorized('Geçersiz token.');
        }
    }

    /**
     * 401 Unauthorized yanıtı gönderir.
     * @param string $message
     */
    protected function sendUnauthorized(string $message): void
    {
        http_response_code(401);
        echo json_encode(['durum' => 'hata', 'mesaj' => $message]);
        exit;
    }
}
