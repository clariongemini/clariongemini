<?php
// config/iyzico_ayarlar.php

use Iyzipay\Options;

// Ortam değişkenlerinden Iyzico ayarlarını yükle
$apiKey = getenv('IYZICO_API_KEY');
$secretKey = getenv('IYZICO_SECRET_KEY');
$baseUrl = getenv('IYZICO_BASE_URL');

if (!$apiKey || !$secretKey || !$baseUrl) {
    // Gerekli ortam değişkenleri tanımlanmamışsa hata ver.
    // Bu, uygulamanın yanlış yapılandırma ile çalışmasını engeller.
    throw new \RuntimeException("Iyzico API anahtarları veya temel URL'si için ortam değişkenleri ayarlanmamış.");
}

$options = new Options();
$options->setApiKey($apiKey);
$options->setSecretKey($secretKey);
$options->setBaseUrl($baseUrl);

define('IYZICO_OPTIONS', $options);
