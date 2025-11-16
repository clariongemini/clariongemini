<?php
namespace ProSiparis\Controllers;

require_once __DIR__ . '/../Service/FeedService.php';

use ProSiparis\Service\FeedService;
use ProSiparis\Core\Request;

class FeedController
{
    private FeedService $feedService;

    public function __construct()
    {
        global $pdo; // PDO'nun global olarak erişilebilir olduğunu varsayıyoruz
        $this->feedService = new FeedService($pdo);
    }

    public function generateSitemap(Request $request): void
    {
        $this->feedService->generateSitemap();
    }

    public function generateGoogleMerchantFeed(Request $request): void
    {
        $this->feedService->generateGoogleMerchantFeed();
    }

    public function generateBingShoppingFeed(Request $request): void
    {
        // Bing'in formatı Google'a çok benzediği için aynı metodu kullanabiliriz.
        // Gerekirse gelecekte özelleştirilebilir.
        $this->feedService->generateGoogleMerchantFeed();
    }
}
