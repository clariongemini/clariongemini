<?php
namespace FulcrumOS\Core;

class Request
{
    /**
     * İsteğin HTTP metodunu döndürür (GET, POST, vb.).
     * @return string
     */
    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * İsteğin URI yolunu döndürür.
     * @return string
     */
    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {
            return $path;
        }
        return substr($path, 0, $position);
    }

    /**
     * Gelen isteğin gövdesini (body) bir dizi olarak döndürür.
     * application/json ve multipart/form-data destekler.
     * @return array
     */
    public function getBody(): array
    {
        if ($this->getMethod() === 'GET') {
            return [];
        }

        // Eğer istek JSON ise
        if (isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
            $body = file_get_contents('php://input');
            return json_decode($body, true) ?: [];
        }

        // Eğer istek multipart/form-data ise
        // Normal form verilerini $_POST'tan al
        $data = $_POST;

        // Genellikle JSON olarak gönderilen karmaşık verileri (örn: varyantlar)
        // bir form alanından (örn: 'json_payload') alıp decode edebiliriz.
        if (isset($data['json_payload'])) {
            $jsonData = json_decode($data['json_payload'], true);
            if ($jsonData) {
                unset($data['json_payload']);
                $data = array_merge($data, $jsonData);
            }
        }

        return $data;
    }

    /**
     * Yüklenen dosyaları $_FILES'tan alır.
     * @return array
     */
    public function getFiles(): array
    {
        return $_FILES;
    }

    /**
     * Authorization başlığından Bearer token'ı ayıklar.
     * @return string|null
     */
    public function getBearerToken(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (!$authHeader) {
            return null;
        }

        $parts = explode(' ', $authHeader);
        if (count($parts) !== 2 || $parts[0] !== 'Bearer') {
            return null;
        }

        return $parts[1];
    }
}
