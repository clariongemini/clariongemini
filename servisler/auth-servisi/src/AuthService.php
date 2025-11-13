<?php
namespace FulcrumOS\Service;

use PDO;
use Firebase\JWT\JWT;

class AuthService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Yeni bir kullanıcı kaydı oluşturur. Varsayılan olarak 'kullanici' rolü atanır.
     * @param array $data ['ad_soyad', 'eposta', 'parola']
     * @return array Başarı veya hata durumu
     */
    public function kayitOl(array $data): array
    {
        if (empty($data['ad_soyad']) || empty($data['eposta']) || empty($data['parola'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Ad soyad, e-posta ve parola alanları zorunludur.'];
        }

        if (!filter_var($data['eposta'], FILTER_VALIDATE_EMAIL)) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'Geçersiz e-posta formatı.'];
        }

        $parola_hash = password_hash($data['parola'], PASSWORD_DEFAULT);
        $varsayilan_rol_id = 4; // 'kullanici' rolünün ID'si

        $sql = "INSERT INTO kullanicilar (ad_soyad, eposta, parola, rol_id) VALUES (:ad_soyad, :eposta, :parola, :rol_id)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':ad_soyad' => $data['ad_soyad'],
                ':eposta' => $data['eposta'],
                ':parola' => $parola_hash,
                ':rol_id' => $varsayilan_rol_id
            ]);
            return ['basarili' => true, 'kod' => 201, 'mesaj' => 'Kayıt başarıyla oluşturuldu.'];
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['basarili' => false, 'kod' => 409, 'mesaj' => 'Bu e-posta adresi zaten kayıtlı.'];
            }
            return ['basarili' => false, 'kod' => 500, 'mesaj' => 'Veritabanı hatası: ' . $e->getMessage()];
        }
    }

    /**
     * Kullanıcı girişi yapar ve JWT döndürür. JWT, kullanıcının yetkilerini ve fiyat listesi ID'sini içerir.
     * @param array $data ['eposta', 'parola']
     * @return array Başarı veya hata durumu, başarılı ise token
     */
    public function girisYap(array $data): array
    {
        if (empty($data['eposta']) || empty($data['parola'])) {
            return ['basarili' => false, 'kod' => 400, 'mesaj' => 'E-posta ve parola alanları zorunludur.'];
        }

        $sql = "
            SELECT
                k.id, k.ad_soyad, k.parola, r.rol_adi, r.fiyat_listesi_id,
                GROUP_CONCAT(y.yetki_kodu) as yetkiler
            FROM kullanicilar k
            JOIN roller r ON k.rol_id = r.rol_id
            LEFT JOIN rol_yetki_iliskisi ryi ON r.rol_id = ryi.rol_id
            LEFT JOIN yetkiler y ON ryi.yetki_id = y.yetki_id
            WHERE k.eposta = :eposta
            GROUP BY k.id, r.rol_adi, r.fiyat_listesi_id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eposta' => $data['eposta']]);
        $kullanici = $stmt->fetch();

        if ($kullanici && password_verify($data['parola'], $kullanici['parola'])) {
            $simdiki_zaman = time();
            $gecerlilik_sonu = $simdiki_zaman + JWT_EXPIRATION_TIME;

            $yetkilerListesi = $kullanici['yetkiler'] ? explode(',', $kullanici['yetkiler']) : [];
            // Fiyat listesi ID'si null ise, varsayılan perakende listesi (1) kullanılır.
            $fiyatListesiId = $kullanici['fiyat_listesi_id'] ?? 1;

            $payload = [
                'iss' => JWT_ISSUER,
                'aud' => JWT_AUDIENCE,
                'iat' => $simdiki_zaman,
                'exp' => $gecerlilik_sonu,
                'data' => [
                    'kullanici_id' => (int)$kullanici['id'],
                    'rol' => $kullanici['rol_adi'],
                    'yetkiler' => $yetkilerListesi,
                    'fiyat_listesi_id' => (int)$fiyatListesiId
                ]
            ];

            $jwt = JWT::encode($payload, JWT_SECRET_KEY, 'HS256');

            return [
                'basarili' => true,
                'kod' => 200,
                'veri' => [
                    'token' => $jwt
                ]
            ];
        }

        return ['basarili' => false, 'kod' => 401, 'mesaj' => 'E-posta veya parola hatalı.'];
    }
}
