<?php
namespace ProSiparis\Destek;

use PDO;

class DestekService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getKullaniciTalepleri(int $kullaniciId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM destek_talepleri WHERE kullanici_id = ? ORDER BY olusturma_tarihi DESC");
        $stmt->execute([$kullaniciId]);
        return ['basarili' => true, 'kod' => 200, 'veri' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function talepOlustur(int $kullaniciId, array $veri): array
    {
        $sql = "INSERT INTO destek_talepleri (kullanici_id, konu) VALUES (?, ?)";
        $this->pdo->prepare($sql)->execute([$kullaniciId, $veri['konu']]);
        $talepId = $this->pdo->lastInsertId();

        // İlk mesajı da ekle
        $this->mesajGonder($talepId, $kullaniciId, $veri);

        return ['basarili' => true, 'kod' => 201, 'veri' => ['talep_id' => $talepId]];
    }

    public function mesajGonder(int $talepId, int $kullaniciId, array $veri): array
    {
        $sql = "INSERT INTO destek_mesajlari (talep_id, gonderen_id, mesaj) VALUES (?, ?, ?)";
        $this->pdo->prepare($sql)->execute([$talepId, $kullaniciId, $veri['mesaj']]);
        return ['basarili' => true, 'kod' => 201];
    }

    // ... Admin metodları
}
