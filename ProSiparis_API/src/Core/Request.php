<?php
namespace ProSiparis\Core;

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
     * Gelen JSON gövdesini (body) çözer ve bir dizi olarak döndürür.
     * @return array
     */
    public function getBody(): array
    {
        if ($this->getMethod() === 'GET') {
            return [];
        }

        $body = file_get_contents('php://input');
        if (empty($body)) {
            return [];
        }

        return json_decode($body, true) ?: [];
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
