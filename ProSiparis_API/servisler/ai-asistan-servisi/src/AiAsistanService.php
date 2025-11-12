<?php
namespace ProSiparis\AiAsistan;

use PDO;

class AiAsistanService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // Olayları işleyecek metod
    public function vektoruGuncelle(array $veri): void
    {
        try {
            // Gemini Embedding API çağrısını simüle et
            $aciklamaVektoru = $this->getEmbeddingVector($veri['aciklama'] ?? '');
            // Kategori için de aynı işlem yapılabilir. Şimdilik null bırakalım.
            $kategoriVektoru = null;

            $sql = "
                INSERT INTO urun_vektorleri (varyant_id, urun_adi, aciklama_vektoru, kategori_vektoru)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                urun_adi = VALUES(urun_adi),
                aciklama_vektoru = VALUES(aciklama_vektoru),
                kategori_vektoru = VALUES(kategori_vektoru)
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $veri['varyant_id'],
                $veri['urun_adi'],
                json_encode($aciklamaVektoru),
                json_encode($kategoriVektoru)
            ]);

            echo "Vektör veritabanı güncellendi: Varyant ID {$veri['varyant_id']}\n";

        } catch (\Exception $e) {
            error_log("Vektör güncellenirken hata: " . $e->getMessage());
        }
    }

    /**
     * Gemini Embedding API'sine yapılan çağrıyı simüle eder.
     * Gerçekte bir API istemcisi kullanılır.
     * @param string $text Vektöre dönüştürülecek metin.
     * @return array 768 elemanlı bir vektör dizisi (simülasyon).
     */
    private function getEmbeddingVector(string $text): array
    {
        // Gerçek API çağrısı yerine rastgele bir vektör üretiyoruz.
        // Vektör boyutunu (örn: 768) sabit tutmak önemlidir.
        $vector = [];
        for ($i = 0; $i < 10; $i++) { // Simülasyon için 10 boyutlu
            $vector[] = (mt_rand() / mt_getrandmax()) * 2 - 1; // -1 ve 1 arasında rastgele float
        }
        return $vector;
    }

    // Müşteri sorularını yanıtlayacak metod
    public function soruSor(string $soru): array
    {
        try {
            // Adım 1: Anlamsal Arama (Vector Search)
            $soruVektoru = $this->getEmbeddingVector($soru);

            $stmt = $this->pdo->query("SELECT varyant_id, urun_adi, aciklama_vektoru FROM urun_vektorleri");
            $urunVektorleri = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $benzerlikSkorlari = [];
            foreach ($urunVektorleri as $urun) {
                $urunVektoru = json_decode($urun['aciklama_vektoru'], true);
                if (is_array($urunVektoru)) {
                    $benzerlikSkorlari[$urun['varyant_id']] = $this->cosineSimilarity($soruVektoru, $urunVektoru);
                }
            }
            arsort($benzerlikSkorlari); // En yüksek skordan düşüğe sırala
            $enIyiVaryantIdler = array_slice(array_keys($benzerlikSkorlari), 0, 5);

            if (empty($enIyiVaryantIdler)) {
                return ['basarili' => true, 'veri' => ['cevap' => 'Üzgünüm, aradığınızla tam eşleşen bir ürün bulamadım. Başka bir şekilde tarif etmeyi dener misiniz?']];
            }

            // Adım 2: Canlı Veri Zenginleştirme
            $idString = implode(',', $enIyiVaryantIdler);
            $urunDetaylari = $this->internalApiCall("http://katalog-servisi/internal/varyant-detaylari?ids={$idString}");
            $stokDurumlari = $this->internalApiCall("http://envanter-servisi/internal/stok-durumu?varyant_ids={$idString}");

            // Adım 3: Nihai Cevap Üretme (LLM Generation Simülasyonu)
            $prompt = "Kullanıcı şunu sordu: '{$soru}'. Ben de şu ürünleri buldum:\n";
            $bulunanUrunSayisi = 0;
            foreach ($enIyiVaryantIdler as $id) {
                if (isset($urunDetaylari[$id])) {
                    $detay = $urunDetaylari[$id];
                    $stokAdedi = $stokDurumlari[$id][0]['stok'] ?? 0;
                    if ($stokAdedi > 0) {
                        $bulunanUrunSayisi++;
                        $prompt .= "- {$detay['urun_adi']} (SKU: {$detay['varyant_sku']}). Stokta {$stokAdedi} adet var.\n";
                    }
                }
            }

            if ($bulunanUrunSayisi === 0) {
                $cevap = "Aradığınız kriterlere yakın bazı ürünler buldum ancak şu an stokta görünmüyorlar. Belki benzer başka ürünlere göz atmak istersiniz?";
            } else {
                 $cevap = "Harika bir seçim! Sorunuza dayanarak size önerebileceğim birkaç ürün var:\n\n" . $prompt . "\nUmarım aradığınızı bulmanıza yardımcı olabilmişimdir!";
            }

            return ['basarili' => true, 'veri' => ['cevap' => $cevap]];

        } catch (\Exception $e) {
            error_log("AI Asistanı soru yanıtlarken hata: " . $e->getMessage());
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Asistan şu anda yanıt veremiyor.'];
        }
    }

    private function internalApiCall(string $url): ?array
    {
        $responseJson = @file_get_contents($url);
        if ($responseJson === false) {
            error_log("Dahili AI Asistan API çağrısı başarısız oldu: $url");
            return null;
        }
        $response = json_decode($responseJson, true);
        return ($response && isset($response['basarili']) && $response['basarili']) ? $response['veri'] : null;
    }

    /**
     * İki vektör arasındaki kosinüs benzerliğini hesaplar.
     */
    private function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $count = count($vec1);
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vec1[$i] * $vec2[$i];
            $normA += $vec1[$i] * $vec1[$i];
            $normB += $vec2[$i] * $vec2[$i];
        }
        $normA = sqrt($normA);
        $normB = sqrt($normB);
        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }
        return $dotProduct / ($normA * $normB);
    }
}
