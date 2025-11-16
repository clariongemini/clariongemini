<?php
// FulcrumOS v10.3: Entegrasyon Servisi - Olay İşleyici Sınıfı

class OlayIsleyici {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Gelen olayı işler, veriyi dönüştürür ve loglar.
     * @param array $olayVerisi RabbitMQ'dan gelen zengin olay verisi
     */
    public function isle($olayVerisi) {
        $olayTipi = $olayVerisi['olay_adi'];

        try {
            // Olay tipine göre ilgili işleyiciyi çağır
            switch ($olayTipi) {
                case 'siparis.kargolandi':
                    $this->siparisKargolandiIsle($olayVerisi);
                    break;
                case 'iade.odeme_basarili':
                    $this->iadeOdemeBasariliIsle($olayVerisi);
                    break;
                case 'tedarik.mal_kabul_yapildi':
                    $this->tedarikMalKabulYapildiIsle($olayVerisi);
                    break;
                default:
                    // Bilinmeyen olayları logla ama hata verme
                    error_log("Bilinmeyen olay tipi alındı: " . $olayTipi);
                    break;
            }
        } catch (Exception $e) {
            error_log("Olay işlenirken hata oluştu ($olayTipi): " . $e->getMessage());
            // Burada olayı 'hata' olarak loglama veya yeniden deneme mekanizması düşünülebilir.
        }
    }

    private function siparisKargolandiIsle($olay) {
        // Bu bir simülasyon. Normalde Organizasyon-Servisi'ne internal API çağrısı yapılır.
        $saticiBilgileri = $this->getSaticiBilgileri();

        $donusturulmusVeri = [
            "islem_tipi" => "SATIS_FATURASI",
            "olay_referansi" => "siparis_id_" . $olay['siparis']['id'],
            "islem_tarihi" => date('c'),
            "satici_bilgileri" => $saticiBilgileri,
            "musteri_bilgileri" => [
                "ad_soyad" => $olay['siparis']['musteri']['ad_soyad'],
                "adres" => $olay['siparis']['teslimat_adresi']['adres_satiri'],
                "eposta" => $olay['siparis']['musteri']['eposta']
            ],
            "urun_satirlari" => array_map(function($urun) {
                return [
                    "sku" => $urun['sku'],
                    "urun_adi" => $urun['urun_adi'],
                    "adet" => $urun['adet'],
                    "birim_fiyat" => $urun['birim_fiyat'],
                    "kdv_orani" => $urun['kdv_orani'],
                    "toplam_tutar" => $urun['toplam_tutar']
                ];
            }, $olay['siparis']['urunler']),
            "toplamlar" => [
                "ara_toplam" => $olay['siparis']['toplamlar']['ara_toplam'],
                "toplam_kdv" => $olay['siparis']['toplamlar']['toplam_kdv'],
                "genel_toplam" => $olay['siparis']['toplamlar']['genel_toplam']
            ]
        ];

        $this->muhasebeLogKaydet('satis', $olay['siparis']['id'], $donusturulmusVeri);
    }

    private function iadeOdemeBasariliIsle($olay) {
        $saticiBilgileri = $this->getSaticiBilgileri();

        $donusturulmusVeri = [
            "islem_tipi" => "IADE_FATURASI",
            "olay_referansi" => "iade_id_" . $olay['iade']['id'],
            "islem_tarihi" => date('c'),
            "satici_bilgileri" => $saticiBilgileri,
            "musteri_bilgileri" => [
                "ad_soyad" => $olay['iade']['musteri']['ad_soyad'],
                "adres" => $olay['iade']['siparis']['teslimat_adresi']['adres_satiri'],
                "eposta" => $olay['iade']['musteri']['eposta']
            ],
            "urun_satirlari" => array_map(function($urun) {
                return [
                    "sku" => $urun['sku'],
                    "urun_adi" => $urun['urun_adi'],
                    "adet" => $urun['adet'],
                    "birim_fiyat" => $urun['birim_fiyat'],
                    "kdv_orani" => $urun['kdv_orani'],
                    "toplam_tutar" => $urun['toplam_tutar']
                ];
            }, $olay['iade']['urunler']),
            "toplamlar" => [
                "genel_toplam" => $olay['iade']['toplam_iade_tutari']
            ]
        ];

        $this->muhasebeLogKaydet('iade', $olay['iade']['id'], $donusturulmusVeri);
    }

    private function tedarikMalKabulYapildiIsle($olay) {
        $saticiBilgileri = $this->getSaticiBilgileri();

        $donusturulmusVeri = [
            "islem_tipi" => "MALIYET_KAYDI",
            "olay_referansi" => "po_id_" . $olay['tedarik']['po_id'],
            "islem_tarihi" => date('c'),
            "satici_bilgileri" => [ // Bu durumda satıcı, tedarikçidir.
                "firma_unvani" => $olay['tedarik']['tedarikci_adi'],
            ],
            "musteri_bilgileri" => $saticiBilgileri, // Alıcı biziz.
            "urun_satirlari" => array_map(function($urun) {
                return [
                    "sku" => $urun['sku'],
                    "urun_adi" => $urun['urun_adi'],
                    "adet" => $urun['gelen_adet'],
                    "birim_fiyat" => $urun['maliyet'], // Maliyet, birim fiyattır
                    "kdv_orani" => $urun['kdv_orani'],
                    "toplam_tutar" => $urun['gelen_adet'] * $urun['maliyet']
                ];
            }, $olay['tedarik']['gelen_urunler']),
            "toplamlar" => [
                "genel_toplam" => $olay['tedarik']['toplam_maliyet']
            ]
        ];

        $this->muhasebeLogKaydet('maliyet', $olay['tedarik']['po_id'], $donusturulmusVeri);
    }

    /**
     * Dönüştürülen veriyi muhasebe_loglari tablosuna kaydeder.
     */
    private function muhasebeLogKaydet($olayTipi, $referansId, $veri) {
        $jsonVeri = json_encode($veri, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $stmt = $this->db->prepare(
            "INSERT INTO muhasebe_loglari (olay_tipi, referans_id, durum, olusturulan_xml_veya_json) VALUES (?, ?, 'beklemede', ?)"
        );
        $stmt->execute([$olayTipi, $referansId, $jsonVeri]);

        echo "Muhasebe logu başarıyla oluşturuldu. Referans ID: $referansId\n";
    }

    /**
     * Simülasyon: Normalde Organizasyon-Servisi'ne /internal/ bir API çağrısı yapar.
     */
    private function getSaticiBilgileri() {
        return [
            "firma_unvani" => "FulcrumOS Satış A.Ş.",
            "vergi_dairesi" => "Büyük Mükellefler",
            "vergi_no" => "1234567890",
            "firma_adresi" => "İstanbul, Türkiye"
        ];
    }
}
