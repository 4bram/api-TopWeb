<?php
class Router
{
    private $routes = [];
    private $version;
    private $basePath;

    public function __construct($version = 'v1', $basePath = '')
    {
        $this->version = $version;
        $this->basePath = rtrim($basePath, '/');
    }

       public function addRoute($method, $path, $handler, $public = false)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => "/api/{$this->version}" . $path,
            'handler' => $handler,
            'public' => $public
        ];
    }

    public function resolve()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!empty($this->basePath) && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        $uri = '/' . ltrim($uri, '/');

        foreach ($this->routes as $route) {
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches);
                return ['handler' => $route['handler'], 'params' => $matches, 'public' => $route['public']];
            }
        }

        return null;
    }

    public function dispatch()
    {
        $result = $this->resolve();

        if ($result === null) {
            http_response_code(404);
            echo json_encode(['message' => 'Ruta no encontrada']);
            return;
        }

        return call_user_func_array($result['handler'], $result['params']);
    }
}
?>