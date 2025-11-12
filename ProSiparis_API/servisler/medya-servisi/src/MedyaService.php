<?php
namespace ProSiparis\Medya;

use PDO;
use PDOException;

class MedyaService
{
    private PDO $pdo;
    private string $uploadDir;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        // __DIR__ -> /app/ProSiparis_API/servisler/medya-servisi/src
        $this->uploadDir = dirname(__DIR__) . '/uploads';
    }

    private function yetkiKontrol(string $gerekliYetki): bool
    {
        $yetkiler = $_SERVER['HTTP_X_PERMISSIONS'] ?? '';
        return in_array($gerekliYetki, explode(',', $yetkiler));
    }

    public function listeleMedyalar(): array
    {
        if (!$this->yetkiKontrol('medya_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }

        $stmt = $this->pdo->query("SELECT dosya_id, dosya_adi, yol, dosya_tipi, boyut, yuklenme_tarihi FROM medya_dosyalari ORDER BY yuklenme_tarihi DESC");
        $medyalar = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Yol'a base URL ekleyerek tam URL oluştur
        $baseUrl = 'http://' . $_SERVER['HTTP_HOST']; // Gerçek uygulamada bu config'den gelmeli
        foreach ($medyalar as &$medya) {
            $medya['url'] = $baseUrl . '/uploads/' . basename($medya['yol']);
        }

        return ['basarili' => true, 'kod' => 200, 'veri' => $medyalar];
    }

    public function yukleDosya(array $file): array
    {
        if (!$this->yetkiKontrol('medya_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Dosya yüklenirken bir hata oluştu. Hata kodu: ' . $file['error']];
        }

        // Güvenlik için dosya adını temizle ve benzersiz yap
        $dosyaAdi = basename($file['name']);
        $uzanti = pathinfo($dosyaAdi, PATHINFO_EXTENSION);
        $benzersizAd = uniqid('medya_', true) . '.' . $uzanti;
        $hedefYol = $this->uploadDir . '/' . $benzersizAd;

        if (!move_uploaded_file($file['tmp_name'], $hedefYol)) {
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Dosya sunucuya taşınamadı.'];
        }

        try {
            $sql = "INSERT INTO medya_dosyalari (dosya_adi, yol, dosya_tipi, boyut, yukleyen_kullanici_id) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $dosyaAdi,
                $hedefYol,
                $file['type'],
                $file['size'],
                $_SERVER['HTTP_X_USER_ID'] ?? null
            ]);
            $id = $this->pdo->lastInsertId();

            $baseUrl = 'http://' . $_SERVER['HTTP_HOST'];
            $url = $baseUrl . '/uploads/' . $benzersizAd;

            return ['basarili' => true, 'kod' => 201, 'veri' => ['dosya_id' => $id, 'dosya_adi' => $dosyaAdi, 'url' => $url]];
        } catch (PDOException $e) {
            // Yüklenen dosyayı geri al
            unlink($hedefYol);
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }

    public function silMedya(int $id): array
    {
        if (!$this->yetkiKontrol('medya_yonet')) {
            return ['basarili' => false, 'kod' => 403, 'mesaj' => 'Yetkisiz erişim.'];
        }

        $stmt = $this->pdo->prepare("SELECT yol FROM medya_dosyalari WHERE dosya_id = ?");
        $stmt->execute([$id]);
        $dosya = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dosya) {
            return ['basarili' => false, 'kod' => 404, 'mesaj' => 'Medya dosyası bulunamadı.'];
        }

        // Önce veritabanından silmeyi dene
        $this->pdo->beginTransaction();
        try {
            $deleteStmt = $this->pdo->prepare("DELETE FROM medya_dosyalari WHERE dosya_id = ?");
            $deleteStmt->execute([$id]);

            // Sonra dosyayı diskten sil
            if (file_exists($dosya['yol'])) {
                unlink($dosya['yol']);
            }

            $this->pdo->commit();
            return ['basarili' => true, 'kod' => 200, 'mesaj' => 'Medya dosyası başarıyla silindi.'];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }
}
