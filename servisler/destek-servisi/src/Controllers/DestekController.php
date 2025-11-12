<?php
namespace ProSiparis\Destek\Controllers;

use PDO;
use Exception;

class DestekController
{
    private PDO $pdo;
    private $requestData;
    private $yapanKullaniciId;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->requestData = json_decode(file_get_contents('php://input'), true);
        $this->yapanKullaniciId = $_SERVER['HTTP_X_USER_ID'] ?? 0;
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
            $durum = $_GET['durum'] ?? null;
            $sql = "SELECT dt.*, k.ad_soyad as musteri_adi FROM destek_talepleri dt JOIN kullanicilar k ON dt.kullanici_id = k.id";
            $params = [];

            if ($durum && in_array($durum, ['acik', 'cevaplandi', 'kapandi'])) {
                $sql .= " WHERE dt.durum = :durum";
                $params[':durum'] = $durum;
            }

            $sql .= " ORDER BY dt.son_guncelleme_tarihi DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $talepler = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse(['durum' => 'basarili', 'veri' => $talepler]);
        } catch (Exception $e) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Destek talepleri listelenirken bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    public function detayGetir(int $talepId): void
    {
        try {
            $sqlTalep = "SELECT dt.*, k.ad_soyad as musteri_adi FROM destek_talepleri dt JOIN kullanicilar k ON dt.kullanici_id = k.id WHERE dt.talep_id = :talep_id";
            $stmtTalep = $this->pdo->prepare($sqlTalep);
            $stmtTalep->execute([':talep_id' => $talepId]);
            $talep = $stmtTalep->fetch(PDO::FETCH_ASSOC);

            if (!$talep) {
                $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Destek talebi bulunamadı.'], 404);
                return;
            }

            $sqlMesajlar = "SELECT * FROM destek_talep_mesajlari WHERE talep_id = :talep_id ORDER BY gonderilme_tarihi ASC";
            $stmtMesajlar = $this->pdo->prepare($sqlMesajlar);
            $stmtMesajlar->execute([':talep_id' => $talepId]);
            $mesajlar = $stmtMesajlar->fetchAll(PDO::FETCH_ASSOC);

            $talep['mesajlar'] = $mesajlar;
            $this->jsonResponse(['durum' => 'basarili', 'veri' => $talep]);
        } catch (Exception $e) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Talep detayı getirilirken bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    public function mesajGonder(int $talepId): void
    {
        $mesaj = $this->requestData['mesaj'] ?? '';
        if (empty($mesaj)) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Mesaj içeriği boş olamaz.'], 400);
            return;
        }

        $this->pdo->beginTransaction();
        try {
            $sqlMesaj = "INSERT INTO destek_talep_mesajlari (talep_id, gonderen_id, mesaj) VALUES (:talep_id, :gonderen_id, :mesaj)";
            $stmtMesaj = $this->pdo->prepare($sqlMesaj);
            $stmtMesaj->execute([':talep_id' => $talepId, ':gonderen_id' => $this->yapanKullaniciId, ':mesaj' => $mesaj]);

            $sqlTalep = "UPDATE destek_talepleri SET durum = 'cevaplandi' WHERE talep_id = :talep_id";
            $stmtTalep = $this->pdo->prepare($sqlTalep);
            $stmtTalep->execute([':talep_id' => $talepId]);

            $this->pdo->commit();
            $this->jsonResponse(['durum' => 'basarili', 'mesaj' => 'Mesaj başarıyla gönderildi.'], 201);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Mesaj gönderilirken bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }

    public function durumGuncelle(int $talepId): void
    {
        $yeniDurum = $this->requestData['durum'] ?? '';
        $izinVerilenDurumlar = ['acik', 'cevaplandi', 'kapandi'];

        if (empty($yeniDurum) || !in_array($yeniDurum, $izinVerilenDurumlar)) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Geçersiz durum değeri.'], 400);
            return;
        }

        try {
            $sql = "UPDATE destek_talepleri SET durum = :durum WHERE talep_id = :talep_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':durum' => $yeniDurum, ':talep_id' => $talepId]);

            if ($stmt->rowCount() === 0) {
                $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Talep bulunamadı veya durum zaten aynı.'], 404);
                return;
            }

            $this->jsonResponse(['durum' => 'basarili', 'mesaj' => 'Talep durumu başarıyla güncellendi.']);
        } catch (Exception $e) {
            $this->jsonResponse(['durum' => 'hata', 'mesaj' => 'Durum güncellenirken bir hata oluştu: ' . $e->getMessage()], 500);
        }
    }
}
