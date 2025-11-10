-- Auth-Servisi Veritabanı Şeması
CREATE TABLE `fiyat_listeleri` ( `liste_id` INT AUTO_INCREMENT PRIMARY KEY, `liste_adi` VARCHAR(100) NOT NULL UNIQUE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `roller` ( `rol_id` INT AUTO_INCREMENT PRIMARY KEY, `rol_adi` VARCHAR(100) NOT NULL UNIQUE, `fiyat_listesi_id` INT NULL, FOREIGN KEY (`fiyat_listesi_id`) REFERENCES `fiyat_listeleri`(`liste_id`) ON DELETE SET NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `yetkiler` ( `yetki_id` INT AUTO_INCREMENT PRIMARY KEY, `yetki_kodu` VARCHAR(100) NOT NULL UNIQUE, `aciklama` VARCHAR(255) NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `rol_yetki_iliskisi` ( `rol_id` INT NOT NULL, `yetki_id` INT NOT NULL, PRIMARY KEY (`rol_id`, `yetki_id`), FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`) ON DELETE CASCADE, FOREIGN KEY (`yetki_id`) REFERENCES `yetkiler`(`yetki_id`) ON DELETE CASCADE ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE `kullanicilar` ( `id` INT AUTO_INCREMENT PRIMARY KEY, `rol_id` INT NOT NULL, `ad_soyad` VARCHAR(100) NOT NULL, `eposta` VARCHAR(100) NOT NULL UNIQUE, `parola` VARCHAR(255) NOT NULL, FOREIGN KEY (`rol_id`) REFERENCES `roller`(`rol_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- BAŞLANGIÇ VERİLERİ
INSERT INTO `fiyat_listeleri` (`liste_id`, `liste_adi`) VALUES (1, 'Perakende'), (2, 'Bayi');
INSERT INTO `roller` (`rol_id`, `rol_adi`, `fiyat_listesi_id`) VALUES (1, 'super_admin', 1), (2, 'kullanici', 1), (3, 'bayi', 2), (4, 'depo_gorevlisi', NULL);
INSERT INTO `yetkiler` (`yetki_id`, `yetki_kodu`) VALUES (1,'tedarikci_yonet'),(2,'satin_alma_yonet'),(3,'satin_alma_teslim_al'),(4,'iade_yonet'),(5,'iade_teslim_al'),(6,'envanter_duzelt'),(7,'rapor_olustur'),(8, 'depo_yonet');
INSERT INTO `rol_yetki_iliskisi` (`rol_id`, `yetki_id`) VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(4,3),(4,5),(4,6);
