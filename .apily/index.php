<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

\Nahid\Apily\Utilities\Config::init();

$files = glob(getcwd() . '/.apily/**/*.api');
$apis = [];
foreach ($files as $file) {
    $requestFilePath = str_replace(getcwd() . '/.apily/', '', $file);
    $apiName = str_replace(['.api', '/'], ['', '.'], $requestFilePath);
    $apiData = json_decode(file_get_contents($file), true);
    $method = $apiData['http']['method'] ?? 'GET';
    $path = $apiData['http']['path'] ?? '/';
    $reqIdentifier = $method . ' ' . ltrim($path, '/');
    $apis[$reqIdentifier] = [
        'name' => $apiName,
        'method' => $apiData['http']['method'] ?? 'GET',
        'path' => $apiData['http']['path'] ?? 'N/A',
        'mock' => $apiData['mock'] ?? [],
    ];
}

$request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();


function findMatchingUri($serverUri, $uriArray) {
    $serverUris =  explode(' ', trim($serverUri), 2);

    if (count($serverUris) < 2) {
        return null;
    }
    // Split the incoming URI into method and path
    list($method, $path) = $serverUris;
    $path = trim($path);

    foreach ($uriArray as $routeKey => $meta) {
        // Split the route key into method and path
        list($routeMethod, $routePath) = explode(' ', trim($routeKey), 2);
        $routePath = trim($routePath);

        // Skip if methods don't match
        if (strtoupper($routeMethod) !== strtoupper($method)) {
            continue;
        }

        // Extract placeholders (e.g., "args.id", "userId")
        preg_match_all('/{{(.*?)}}/', $routePath, $matches);
        $placeholders = $matches[1];


        // Build a regex pattern by replacing placeholders with capture groups
        $pattern = preg_replace('/{{.*?}}/', '([^\/]+)', $routePath);
        $regex = "#^{$routeMethod}\\s+{$pattern}$#i";

        // Check if the incoming URI matches the regex
        if (preg_match($regex, "{$method} {$path}", $matches)) {
            array_shift($matches); // Remove the full match

            // Map captured values to placeholder keys
            $params = [];
            foreach ($placeholders as $index => $placeholder) {
                $keys = explode('.', $placeholder);
                $current = &$params;
                foreach ($keys as $key) {
                    if (!isset($current[$key])) {
                        $current[$key] = [];
                    }
                    $current = &$current[$key];
                }
                $current = $matches[$index];
            }

            return [
                'meta' => $meta,
                'params' => $params,
            ];
        }
    }

    return null; // No match found
}

$requestUri = $request->getUri()->getPath();


$matchedUri = $request->getMethod() . ' ' . ltrim($requestUri, '/');

$matchedApi = null;
$extractedArgs = [];


$result = findMatchingUri($matchedUri, $apis);

if (!$result) {
    header(sprintf('HTTP/%s %s %s', '1.1', 404, 'Not Found'));
    echo '';

    return;
}

$request = $request->withAttribute('uri_params', $result['params']);

$mockServer = new \Nahid\Apily\Server\MockServer($request);

$mockServer->serve($result['meta']['name']);
