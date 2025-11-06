<?php
namespace ProSiparis\Core;

class Router
{
    protected array $routes = [];
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * GET metodu için bir rota tanımlar.
     * @param string $path
     * @param array $callback [ControllerSınıfı, metotAdı]
     * @param array $middlewares
     */
    public function get(string $path, array $callback, array $middlewares = [])
    {
        $this->addRoute('GET', $path, $callback, $middlewares);
    }

    /**
     * POST metodu için bir rota tanımlar.
     * @param string $path
     * @param array $callback
     * @param array $middlewares
     */
    public function post(string $path, array $callback, array $middlewares = [])
    {
        $this->addRoute('POST', $path, $callback, $middlewares);
    }

    /**
     * PUT metodu için bir rota tanımlar.
     * @param string $path
     * @param array $callback
     * @param array $middlewares
     */
    public function put(string $path, array $callback, array $middlewares = [])
    {
        $this->addRoute('PUT', $path, $callback, $middlewares);
    }

    /**
     * DELETE metodu için bir rota tanımlar.
     * @param string $path
     * @param array $callback
     * @param array $middlewares
     */
    public function delete(string $path, array $callback, array $middlewares = [])
    {
        $this->addRoute('DELETE', $path, $callback, $middlewares);
    }

    /**
     * Rota listesine yeni bir rota ekler.
     * @param string $method
     * @param string $path
     * @param array $callback
     * @param array $middlewares
     */
    protected function addRoute(string $method, string $path, array $callback, array $middlewares)
    {
        $this->routes[$method][$path] = ['callback' => $callback, 'middlewares' => $middlewares];
    }

    /**
     * Gelen isteği analiz eder ve uygun rotayı çalıştırır.
     */
    public function dispatch()
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();

        $route = $this->findRoute($method, $path);

        if (!$route) {
            $this->sendNotFound();
            return;
        }

        $middlewares = $route['middlewares'];
        $callback = $route['callback'];
        $params = $route['params'];

        // Middleware'leri çalıştır
        foreach ($middlewares as $middleware) {
            $instance = new $middleware();
            $instance->handle($this->request);
        }

        // Controller'ı ve metodu çağır
        $controller = new $callback[0]();
        call_user_func_array([$controller, $callback[1]], $params);
    }

    /**
     * Verilen metot ve yol için uygun rotayı bulur.
     * @param string $method
     * @param string $path
     * @return array|null
     */
    protected function findRoute(string $method, string $path): ?array
    {
        foreach ($this->routes[$method] ?? [] as $routePath => $data) {
            // Dinamik parametreler için regex (örn: /urunler/{id})
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return array_merge($data, ['params' => $params]);
            }
        }
        return null;
    }

    /**
     * 404 Not Found yanıtı gönderir.
     */
    protected function sendNotFound()
    {
        http_response_code(404);
        echo json_encode(['durum' => 'hata', 'mesaj' => 'Aradığınız kaynak bulunamadı.']);
    }
}
