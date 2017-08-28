<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Parser as YamlParser;
use Slim\Http\Request;
use Slim\Http\Response;

session_start();

// settings
$slimConfig = ['domain' => 'kollus-upload-php.app'];
$twigConfig = ['use_twig_cache' => false, 'debug' => true];
$kollusConfig = [];
$configFilePath = __DIR__ . '/../config.yml';
if (file_exists($configFilePath)) {
    $yamlParser = new YamlParser();
    $parser = $yamlParser->parse(file_get_contents($configFilePath));
    $kollusConfig = $parser['kollus'];
    $twigConfig = $parser['twig'];
}

$settings = [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'domain' => $slimConfig['domain'],
        // Twig View settings
        'view' => [
            'template_path' => __DIR__ . '/../templates/',
            'settings' => $twigConfig,
        ],
        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],
        // kollus settings
        'kollus' => $kollusConfig,
    ],
];

$app = new \Slim\App($settings);

// dependencies
$container = $app->getContainer();
$container['twig'] = function ($c) {
    $settings = $c->get('settings')['view'];

    $view = new \Slim\Views\Twig($settings['template_path'], $settings['settings']);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $c['router'],
        $c['request']->getUri()
    ));
    $view->addExtension(new \Twig_Extension_Debug());

    return $view;
};

$container['kollusApiClient'] = function ($c) {
    $settings = $c->get('settings')['kollus'];

    $apiClient = null;
    if (isset($settings['domain']) &&
        isset($settings['version']) &&
        isset($settings['language_key']) &&
        isset($settings['service_account']['key']) &&
        isset($settings['service_account']['api_access_token'])
    ) {
        // Get API Client
        $apiClient = \Kollus\Component\KollusClient::getApiClientBy(
             $settings['domain'],
             $settings['version'],
             $settings['language_key'],
             $settings['service_account']['key'],
             $settings['service_account']['api_access_token']
        );
    }

    return $apiClient;
};

// routing
$app->get('/', function (Request $request, Response $response) use ($container) {
    $kollusSettings = $this->settings['kollus'];
    $kollusApiClient = $container->get('kollusApiClient');
    /** @var \Kollus\Component\Client\ApiClient $kollusApiClient */

    $twig = $container->get('twig');
    /** @var \Slim\Views\Twig $twig */

    $data['categories'] = [];
    $data['upload_files'] = [];
    if ($kollusApiClient instanceof \Kollus\Component\Client\ApiClient) {
        $data['categories'] = $kollusApiClient->getCategories();

        $result = $kollusApiClient->findUploadFilesByPage(1, ['per_page' => 10]);
        $data['upload_files'] = $result->items;
    }
    $data['config_not_exist'] = empty($kollusSettings) && is_null($kollusApiClient);
    $data['service_account_key'] = isset($kollusSettings['service_account']['key']) ?
        $kollusSettings['service_account']['key'] : null;
    $data['kollus_domain'] = isset($kollusSettings['domain']) ? $kollusSettings['domain'] : null;

    return $twig->render($response, 'index.html.twig', $data);
})->setName('index');

$app->post('/api/upload/create_url', function (Request $request, Response $response) use ($container) {
    $kollusApiClient = $container->get('kollusApiClient');
    /** @var \Kollus\Component\Client\ApiClient $kollusApiClient */

    $postParams = $request->getParsedBody();

    $categoryKey = empty($postParams['category_key']) ? null : $postParams['category_key'];
    $isEncryptionUpload = empty($postParams['use_encryption']) ? null : $postParams['use_encryption'];
    $isAudioUpload = empty($postParams['is_audio_upload']) ? null : $postParams['is_audio_upload'];
    $title = empty($postParams['title']) ? null : $postParams['title'];

    $apiResponse = $kollusApiClient->getUploadURLResponse(
        $categoryKey,
        $isEncryptionUpload,
        $isAudioUpload,
        $title
    );

    return $response->withJson(['result' => $apiResponse], 200);
})->setName('api-upload-create-url');

$app->get('/api/upload_file', function (Request $request, Response $response) use ($container) {
    $kollusApiClient = $container->get('kollusApiClient');
    /** @var \Kollus\Component\Client\ApiClient $kollusApiClient */

    $result = $kollusApiClient->findUploadFilesByPage(1, ['per_page' => 10]);
    $uploadFiles = $result->items;
    /** @var \Kollus\Component\Container\UploadFile[] $uploadFiles */

    $auto_reload = false;
    $per_page = $result->per_page;
    $count = $result->count;
    $items = [];
    foreach ($uploadFiles as $uploadFile) {
        if (in_array($uploadFile->getTranscodingStage(), [0, 1, 12])) {
            $auto_reload = true;
        }

        $items[] = [
            'upload_file_key' => $uploadFile->getUploadFileKey(),
            'media_content_id' => $uploadFile->getMediaContentId(),
            'title' => $uploadFile->getTitle(),
            'transcoding_stage' => $uploadFile->getTranscodingStage(),
            'transcoding_stage_name' => $uploadFile->getTranscodingStageName(),
            'transcoding_progress' => $uploadFile->getTranscodingProgress(),
            'created_at' => $uploadFile->getCreatedAt(),
            'transcoded_at' => $uploadFile->getTranscodedAt(),
        ];
    }

    return $response->withJson(compact('per_page', 'count', 'items', 'auto_reload'), 200);
})->setName('api-upload-file');

$app->run();
