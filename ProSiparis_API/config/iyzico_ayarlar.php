<?php
// config/iyzico_ayarlar.php
// Iyzico API Anahtarları ve Ayarları

class IyzicoOptions {
    public static function getTestOptions() {
        $options = new \Iyzipay\Options();
        $options->setApiKey('sandbox-your-api-key');
        $options->setSecretKey('sandbox-your-secret-key');
        $options->setBaseUrl('https://sandbox-api.iyzipay.com');
        return $options;
    }

    public static function getProdOptions() {
        $options = new \Iyzipay\Options();
        $options->setApiKey('your-production-api-key');
        $options->setSecretKey('your-production-secret-key');
        $options->setBaseUrl('https://api.iyzipay.com');
        return $options;
    }
}

// Ortama göre ayarları seçin (örneğin, başka bir config dosyasından okunabilir)
define('IYZICO_OPTIONS', IyzicoOptions::getTestOptions());
