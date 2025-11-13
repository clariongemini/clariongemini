<?php
namespace FulcrumOS\Middleware;

use FulcrumOS\Core\Request;
use FulcrumOS\Core\Auth;

class PermissionMiddleware
{
    /**
     * @var string Gerekli yetki kodu.
     */
    protected $requiredPermission;

    /**
     * Middleware'i belirli bir yetki gereksinimi ile başlatır.
     * @param string $requiredPermission Kontrol edilecek yetki kodu (örn: 'urun_guncelle').
     */
    public function __construct(string $requiredPermission)
    {
        $this->requiredPermission = $requiredPermission;
    }

    /**
     * Gelen isteği işler ve kullanıcının JWT'sinde gerekli yetkinin olup olmadığını kontrol eder.
     * Bu middleware'in AuthMiddleware'den sonra çalışması zorunludur.
     * @param Request $request
     */
    public function handle(Request $request): void
    {
        // Auth::check() AuthMiddleware tarafından zaten yapılmış olmalı, bu bir ek güvence.
        if (!Auth::check()) {
            $this->sendForbidden('Bu kaynağa erişim için kimlik doğrulaması yapılmalıdır.');
        }

        $user = Auth::user();

        // JWT payload'ında 'yetkiler' dizisinin olup olmadığını ve bir dizi olduğunu kontrol et.
        if (!isset($user->yetkiler) || !is_array($user->yetkiler)) {
            $this->sendForbidden('Yetki bilgileri bulunamadı. Lütfen tekrar giriş yapın.');
        }

        // Kullanıcının yetkileri arasında gerekli yetkinin olup olmadığını kontrol et.
        if (!in_array($this->requiredPermission, $user->yetkiler)) {
            $this->sendForbidden('Bu işlemi yapmak için gerekli yetkiye sahip değilsiniz.');
        }
    }

    /**
     * 403 Forbidden yanıtı gönderir ve işlemi sonlandırır.
     * @param string $message
     */
    protected function sendForbidden(string $message): void
    {
        http_response_code(403); // Forbidden
        echo json_encode(['durum' => 'hata', 'mesaj' => $message]);
        exit;
    }
}
