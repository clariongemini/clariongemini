<?php
// vendor/manual_autoload.php

/**
 * Composer'ın `autoload.php` dosyasının yaptığı işi manuel olarak taklit eder.
 * Bu, sadece `firebase/php-jwt` kütüphanesini yüklemek için basitleştirilmiş bir versiyondur.
 */
spl_autoload_register(function ($class) {
    // Projenin kök isim alanı (namespace)
    $prefix = 'Firebase\\JWT\\';

    // İsim alanının bu yükleyiciye ait olup olmadığını kontrol et
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Hayır, bir sonraki yükleyiciye geç
        return;
    }

    // Sınıf adından kök isim alanını çıkar
    $relative_class = substr($class, $len);

    // İsim alanındaki ayraçları dizin ayraçlarıyla değiştir ve .php ekle
    $file = __DIR__ . '/firebase/php-jwt/src/' . str_replace('\\', '/', $relative_class) . '.php';

    // Dosya mevcutsa, yükle
    if (file_exists($file)) {
        require $file;
    }
});
