<?php
namespace ProSiparis\Service;

use PDO;
use DOMDocument;

class FeedService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function generateSitemap(): void
    {
        header("Content-Type: application/xml; charset=utf-8");
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->appendChild($urlset);

        // Ürün URL'lerini ekle
        $stmt = $this->pdo->query("SELECT slug FROM urunler");
        while ($row = $stmt->fetch()) {
            $this->addUrl($xml, $urlset, "/urun/{$row['slug']}");
        }

        // Kategori URL'lerini ekle
        $stmt = $this->pdo->query("SELECT slug FROM kategoriler");
        while ($row = $stmt->fetch()) {
            $this->addUrl($xml, $urlset, "/kategori/{$row['slug']}");
        }

        // CMS Sayfa URL'lerini ekle (Dahili API Çağrısı)
        $cmsData = $this->internalApiCall('http://cms-servisi/internal/sayfa-sluglari');
        if ($cmsData) {
            foreach ($cmsData as $sayfa) {
                $this->addUrl($xml, $urlset, "/sayfa/{$sayfa['slug']}");
            }
        }

        echo $xml->saveXML();
    }

    public function generateGoogleMerchantFeed(): void
    {
        header("Content-Type: application/xml; charset=utf-8");
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // RSS ve Google Merchant namespace'lerini ekle
        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:g', 'http://base.google.com/ns/1.0');
        $xml->appendChild($rss);

        $channel = $xml->createElement('channel');
        $channel->appendChild($xml->createElement('title', 'ProSiparis Urun Feedi'));
        $channel->appendChild($xml->createElement('link', 'https://www.prosiparis.com'));
        $channel->appendChild($xml->createElement('description', 'Urunlerimizin tam listesi'));
        $rss->appendChild($channel);

        // Ürünleri ve varyantları çek
        $sql = "SELECT u.urun_adi, u.marka, v.varyant_sku, v.gtin, v.mpn, vf.fiyat FROM urun_varyantlari v JOIN urunler u ON v.urun_id = u.urun_id JOIN varyant_fiyatlari vf ON v.varyant_id = vf.varyant_id WHERE vf.fiyat_listesi_id = 1"; // Perakende fiyat listesi
        $stmt = $this->pdo->query($sql);

        while ($urun = $stmt->fetch()) {
            $item = $xml->createElement('item');
            $item->appendChild($this->createGoogleElement($xml, 'g:id', $urun['varyant_sku']));
            $item->appendChild($this->createGoogleElement($xml, 'title', $urun['urun_adi']));
            $item->appendChild($this->createGoogleElement($xml, 'g:price', "{$urun['fiyat']} TRY"));
            $item->appendChild($this->createGoogleElement($xml, 'g:condition', 'new'));

            // Stok durumu için Envanter-Servisi'ne çağrı yap
            $stokData = $this->internalApiCall('http://envanter-servisi/internal/stok-durumu?varyant_id=' . $urun['varyant_id']);
            $stokAdedi = 0;
            if ($stokData && isset($stokData[$urun['varyant_id']])) {
                foreach ($stokData[$urun['varyant_id']] as $depoStok) {
                    $stokAdedi += $depoStok['stok'];
                }
            }
            $availability = $stokAdedi > 0 ? 'in stock' : 'out of stock';
            $item->appendChild($this->createGoogleElement($xml, 'g:availability', $availability));

            $item->appendChild($this->createGoogleElement($xml, 'g:gtin', $urun['gtin']));
            $item->appendChild($this->createGoogleElement($xml, 'g:mpn', $urun['mpn']));
            $item->appendChild($this->createGoogleElement($xml, 'g:brand', $urun['marka']));
            $channel->appendChild($item);
        }

        echo $xml->saveXML();
    }

    private function addUrl(DOMDocument $xml, \DOMElement $urlset, string $loc): void
    {
        $url = $xml->createElement('url');
        $url->appendChild($xml->createElement('loc', "https://www.prosiparis.com{$loc}"));
        $url->appendChild($xml->createElement('lastmod', date('Y-m-d')));
        $urlset->appendChild($url);
    }

    private function createGoogleElement(DOMDocument $xml, string $name, ?string $value): \DOMElement
    {
        // Değer boş ise CData bloğu olmayan boş bir element oluştur
        if ($value === null || $value === '') {
            return $xml->createElement($name);
        }
        $element = $xml->createElement($name);
        $element->appendChild($xml->createCDATASection($value));
        return $element;
    }

    private function internalApiCall(string $url): ?array
    {
        // @-operatörü, hata durumunda warning basmasını engeller.
        $responseJson = @file_get_contents($url);
        if ($responseJson === false) {
            // İlgili servise ulaşılamıyorsa logla ve devam et.
            error_log("Dahili API çağrısı başarısız oldu: $url");
            return null;
        }
        $response = json_decode($responseJson, true);
        return ($response && isset($response['basarili']) && $response['basarili']) ? $response['veri'] : null;
    }
}
