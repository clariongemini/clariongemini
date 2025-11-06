<?php
// api/siparis_olustur.php - Yeni bir sipariş oluşturur.

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST');
require_once __DIR__ . '/../veritabani_baglantisi.php';

// Sadece POST isteklerini kabul et.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Sadece POST metodu kabul edilmektedir.']);
    exit;
}

// POST verilerini al.
$veri = json_decode(file_get_contents('php://input'), true);

// Gerekli alanların kontrolü.
if (
    !isset($veri['kullanici_id']) ||
    !isset($veri['toplam_tutar']) ||
    !isset($veri['sepet']) ||
    !is_array($veri['sepet']) ||
    empty($veri['sepet'])
) {
    http_response_code(400);
    echo json_encode(['durum' => 'hata', 'mesaj' => 'Kullanıcı ID, toplam tutar ve sepet bilgileri zorunludur.']);
    exit;
}

$kullanici_id = $veri['kullanici_id'];
$toplam_tutar = $veri['toplam_tutar'];
$sepet = $veri['sepet'];

// Veritabanı işlemini bir transaction içinde yap.
// Bu, ya tüm sorguların başarılı olmasını ya da hiçbirinin olmamasını sağlar.
try {
    // Transaction'ı başlat.
    $pdo->beginTransaction();

    // 1. Adım: `siparisler` tablosuna ana sipariş kaydını ekle.
    $sql_siparis = "INSERT INTO siparisler (kullanici_id, toplam_tutar) VALUES (:kullanici_id, :toplam_tutar)";
    $stmt_siparis = $pdo->prepare($sql_siparis);
    $stmt_siparis->execute([
        'kullanici_id' => $kullanici_id,
        'toplam_tutar' => $toplam_tutar
    ]);

    // Oluşturulan son siparişin ID'sini al.
    $siparis_id = $pdo->lastInsertId();

    // 2. Adım: Sepetteki her bir ürün için `siparis_detaylari` tablosuna kayıt ekle.
    $sql_detay = "INSERT INTO siparis_detaylari (siparis_id, urun_id, adet, birim_fiyat) VALUES (:siparis_id, :urun_id, :adet, :birim_fiyat)";
    $stmt_detay = $pdo->prepare($sql_detay);

    foreach ($sepet as $urun) {
        $stmt_detay->execute([
            'siparis_id' => $siparis_id,
            'urun_id' => $urun['urun_id'],
            'adet' => $urun['adet'],
            'birim_fiyat' => $urun['birim_fiyat']
        ]);
    }

    // Tüm işlemler başarılıysa, transaction'ı onayla (commit).
    $pdo->commit();

    // Başarılı yanıtı döndür.
    http_response_code(201); // Oluşturuldu
    echo json_encode([
        'durum' => 'basarili',
        'mesaj' => 'Siparişiniz başarıyla alındı.',
        'siparis_id' => $siparis_id
    ]);

} catch (PDOException $e) {
    // Herhangi bir hata olursa, tüm işlemleri geri al (rollback).
    $pdo->rollBack();

    // Hata yanıtı döndür.
    http_response_code(500);
    echo json_encode([
        'durum' => 'hata',
        'mesaj' => 'Sipariş oluşturulurken bir hata oluştu.',
        // 'hata_detayi' => $e->getMessage() // Geliştirme için
    ]);
}
