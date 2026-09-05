<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../core/Router.php';
require_once '../models/AuthMiddleware.php';
require_once '../config/database.php';
require_once '../resources/v1/UserResource.php';
require_once '../resources/v1/ProductResource.php';
require_once '../resources/v2/UserResource.php';

$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $scriptName;

$database = new Database();
$db = $database->getConnection();
$auth = new AuthMiddleware($db);

// --- v1: intacta, protegida con el filtro ---
$routerV1 = new Router('v1', $basePath);
$userResourceV1 = new UserResource();
$productResource = new ProductResource();

$routerV1->addRoute('GET', '/users', [$userResourceV1, 'index']);
$routerV1->addRoute('GET', '/users/{id}', [$userResourceV1, 'show']);
$routerV1->addRoute('POST', '/users', [$userResourceV1, 'store']);
$routerV1->addRoute('PUT', '/users/{id}', [$userResourceV1, 'update']);
$routerV1->addRoute('DELETE', '/users/{id}', [$userResourceV1, 'destroy']);

$routerV1->addRoute('GET', '/productos', [$productResource, 'index']);
$routerV1->addRoute('GET', '/productos/{id}', [$productResource, 'show']);
$routerV1->addRoute('POST', '/productos', [$productResource, 'store']);
$routerV1->addRoute('PUT', '/productos/{id}', [$productResource, 'update']);
$routerV1->addRoute('DELETE', '/productos/{id}', [$productResource, 'destroy']);

// --- v2 ---
$routerV2 = new Router('v2', $basePath);
$userResourceV2 = new UserResourceV2();

$routerV2->addRoute('POST', '/login', [$userResourceV2, 'login'], true); // pública
$routerV2->addRoute('POST', '/logout', [$userResourceV2, 'logout']);     // protegida
$routerV2->addRoute('GET', '/me', [$userResourceV2, 'me']);              // protegida
$routerV2->addRoute('GET', '/users', [$userResourceV1, 'index']);
$routerV2->addRoute('GET', '/users/{id}', [$userResourceV1, 'show']);
$routerV2->addRoute('POST', '/users', [$userResourceV1, 'store']);
$routerV2->addRoute('PUT', '/users/{id}', [$userResourceV1, 'update']);
$routerV2->addRoute('DELETE', '/users/{id}', [$userResourceV1, 'destroy']);

$routerV2->addRoute('GET', '/productos', [$productResource, 'index']);
$routerV2->addRoute('GET', '/productos/{id}', [$productResource, 'show']);
$routerV2->addRoute('POST', '/productos', [$productResource, 'store']);
$routerV2->addRoute('PUT', '/productos/{id}', [$productResource, 'update']);
$routerV2->addRoute('DELETE', '/productos/{id}', [$productResource, 'destroy']);

// --- Resolver ruta ---
$result = $routerV1->resolve() ?? $routerV2->resolve();

if (!$result) {
    http_response_code(404);
    echo json_encode(['message' => 'Ruta no encontrada']);
    exit;
}

// --- Filtro de autenticación (salta si la ruta es pública) ---
if (empty($result['public'])) {
    $authUser = $auth->authenticate(); // responde 401 y hace exit si falla

    $handlerObject = $result['handler'][0];
    if (is_object($handlerObject) && method_exists($handlerObject, 'setAuthUser')) {
        $handlerObject->setAuthUser($authUser);
    }
}

call_user_func_array($result['handler'], $result['params']);
?>