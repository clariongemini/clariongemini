<?php
namespace ProSiparis\Middleware;

use ProSiparis\Core\Request;
use ProSiparis\Core\Auth;

class AdminMiddleware
{
    /**
     * Gelen isteği işler ve kullanıcının admin olup olmadığını kontrol eder.
     * Bu middleware'in AuthMiddleware'den sonra çalışması gerekir.
     * @param Request $request
     */
    public function handle(Request $request): void
    {
        // Önce bir kullanıcının giriş yapmış olduğundan emin ol.
        if (!Auth::check()) {
            $this->sendForbidden('Bu kaynağa erişim için önce kimlik doğrulaması yapılmalıdır.');
        }

        // Kullanıcının rolünün 'admin' olup olmadığını kontrol et.
        $user = Auth::user();
        if (!isset($user->rol) || $user->rol !== 'admin') {
            $this->sendForbidden('Bu kaynağa erişim yetkiniz yok.');
        }
    }

    /**
     * 403 Forbidden yanıtı gönderir.
     * @param string $message
     */
    protected function sendForbidden(string $message): void
    {
        http_response_code(403); // Yasak
        echo json_encode(['durum' => 'hata', 'mesaj' => $message]);
        exit;
    }
}
