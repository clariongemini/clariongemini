<?php
namespace ProSiparis\Tedarik\Controllers;

use PDO;
use Exception;

class TedarikciController
{
    private PDO $pdo;
    private $requestData;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->requestData = json_decode(file_get_contents('php://input'), true);
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
    }

    public function listele(): void
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM tedarikciler ORDER BY firma_adi ASC");
            $tedarikciler = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->jsonResponse(['durum' => 'basarili', 'veri' => $tedarikciler]);
        } catch (Exception $e) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Tedarikçiler listelenirken bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    public function olustur(): void
    {
        if (empty($this->requestData['firma_adi'])) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Firma adı zorunludur.'], 400);
            return;
        }

        $sql = "INSERT INTO tedarikciler (firma_adi, yetkili_kisi, eposta, telefon) VALUES (:firma_adi, :yetkili_kisi, :eposta, :telefon)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':firma_adi' => $this->requestData['firma_adi'],
                ':yetkili_kisi' => $this->requestData['yetkili_kisi'] ?? null,
                ':eposta' => $this->requestData['eposta'] ?? null,
                ':telefon' => $this->requestData['telefon'] ?? null,
            ]);
            $yeniId = $this->pdo->lastInsertId();
            $this->jsonResponse(['durum' => 'basarili', 'mesaj' => 'Tedarikçi başarıyla oluşturuldu.', 'yeni_tedarikci_id' => $yeniId], 201);
        } catch (Exception $e) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Tedarikçi oluşturulurken bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    public function guncelle(int $id): void
    {
        if (empty($this->requestData['firma_adi'])) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Firma adı zorunludur.'], 400);
            return;
        }

        $sql = "UPDATE tedarikciler SET firma_adi = :firma_adi, yetkili_kisi = :yetkili_kisi, eposta = :eposta, telefon = :telefon WHERE tedarikci_id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':firma_adi' => $this->requestData['firma_adi'],
                ':yetkili_kisi' => $this->requestData['yetkili_kisi'] ?? null,
                ':eposta' => $this->requestData['eposta'] ?? null,
                ':telefon' => $this->requestData['telefon'] ?? null,
            ]);

            if ($stmt->rowCount() === 0) {
                $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Tedarikçi bulunamadı veya güncelleme yapılacak bir değişiklik yok.'], 404);
                return;
            }

            $this->jsonResponse(['durum' => 'basarili', 'mesaj' => 'Tedarikçi başarıyla güncellendi.']);
        } catch (Exception $e) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Tedarikçi güncellenirken bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    public function sil(int $id): void
    {
        $sql = "DELETE FROM tedarikciler WHERE tedarikci_id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Tedarikçi bulunamadı.'], 404);
                return;
            }

            $this->jsonResponse(['durum' => 'basarili', 'mesaj' => 'Tedarikçi başarıyla silindi.']);
        } catch (Exception $e) {
            // Veritabanı FOREIGN KEY kısıtlaması (CONSTRAINT) nedeniyle silme işlemi başarısız olabilir.
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Tedarikçi silinirken bir hata oluştu. Bu tedarikçiye ait satın alma siparişleri olabilir.'], 500);
        }
    }
}
