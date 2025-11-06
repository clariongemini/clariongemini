<?php
namespace ProSiparis\Service;

class FileUploadService
{
    private string $uploadDir;

    public function __construct(string $targetSubDir = 'urunler/')
    {
        // Yüklemelerin public klasörü içinde güvenli bir yola yapılmasını sağla
        $this->uploadDir = __DIR__ . '/../../public/uploads/' . $targetSubDir;
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * $_FILES dizisinden bir dosyayı işler ve kaydeder.
     * @param array $file $_FILES['anahtar']
     * @return array Başarı veya hata durumu
     */
    public function handle(array $file): array
    {
        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['basarili' => false, 'mesaj' => 'Dosya yüklenirken bir hata oluştu: ' . $this->codeToMessage($file['error'])];
            }

            // Güvenlik: Dosya boyutunu kontrol et (örn: max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                return ['basarili' => false, 'mesaj' => 'Dosya boyutu 5MB\'dan büyük olamaz.'];
            }

            // Güvenlik: MIME tipini kontrol et (sadece resimlere izin ver)
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileMimeType = mime_content_type($file['tmp_name']);
            if (!in_array($fileMimeType, $allowedMimes)) {
                return ['basarili' => false, 'mesaj' => 'Sadece JPG, PNG, GIF ve WEBP formatında resimlere izin verilmektedir.'];
            }

            // Benzersiz bir dosya adı oluştur
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid('', true) . '.' . strtolower($fileExtension);
            $targetPath = $this->uploadDir . $newFileName;

            // Dosyayı taşı
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return ['basarili' => false, 'mesaj' => 'Dosya sunucuya taşınamadı.'];
            }

            // Başarılı olursa, dosyanın web'den erişilebilir yolunu döndür
            $webPath = 'uploads/urunler/' . $newFileName;
            return ['basarili' => true, 'yol' => $webPath];

        } catch (\Exception $e) {
            return ['basarili' => false, 'mesaj' => 'Dosya yükleme sırasında beklenmedik bir hata oluştu: ' . $e->getMessage()];
        }
    }

    private function codeToMessage(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                return "Yüklenen dosya php.ini dosyasındaki upload_max_filesize direktifini aşıyor.";
            case UPLOAD_ERR_FORM_SIZE:
                return "Yüklenen dosya HTML formundaki MAX_FILE_SIZE direktifini aşıyor.";
            case UPLOAD_ERR_PARTIAL:
                return "Dosya sadece kısmen yüklendi.";
            case UPLOAD_ERR_NO_FILE:
                return "Hiç dosya yüklenmedi.";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Geçici klasör eksik.";
            case UPLOAD_ERR_CANT_WRITE:
                return "Dosya diske yazılamadı.";
            case UPLOAD_ERR_EXTENSION:
                return "Bir PHP eklentisi dosya yüklemeyi durdurdu.";
            default:
                return "Bilinmeyen yükleme hatası.";
        }
    }
}
