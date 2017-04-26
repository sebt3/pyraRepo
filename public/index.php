<?php
/////////////////////////////////////////////////////////////////////////////////////////////
// dependencies
session_start();
require '../vendor/autoload.php';
set_include_path('..'.DIRECTORY_SEPARATOR.'classes');
spl_autoload_register();
//spl_autoload_register(function(){});
spl_autoload_register(function ($classname) {
	if($classname=='ZipArchive') return;
	$class = implode(DIRECTORY_SEPARATOR, explode('\\', $classname));
	require '..'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$class.'.php';
});
$app = new \Slim\App([ 'settings' => json_decode(file_get_contents('../config.json'), true) ]);

/////////////////////////////////////////////////////////////////////////////////////////////
// containers
$container = $app->getContainer();
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('watched');
    $logger->pushHandler(new \Monolog\Handler\StreamHandler('../logs/app.log'));
    return $logger;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],  $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

/*$container['csrf'] = function($container){
    return new \Slim\Csrf\Guard;
};*/
$container['flash'] = function () {	return new \Slim\Flash\Messages; };
$container['trans'] = function ($c) {	return new \Containers\Translate($c); };
$container['auth'] = function ($c) {	return new \Containers\AuthContainer($c); };
$container['menu']  = function ($c) {	return new \Containers\MenuObject($c); };

$container['view'] = function ($container) use ($app) {
    $view = new \Slim\Views\Twig('../templates/', [
        'cache' => false
        //'cache' => 'path/to/cache'
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));
    $view->getEnvironment()->addGlobal('menu',  $container->menu);
    $view->getEnvironment()->addGlobal('flash', $container->flash);
    $view->getEnvironment()->addFunction(new Twig_SimpleFunction('_', $container->trans));
    $view->getEnvironment()->addFunction(new Twig_SimpleFunction('json', 'json_encode', array('is_safe' => array('html'))));

    return $view;
};
$container['notFoundHandler']	= function ($c) {	return new \Containers\NotFoundHandler($c->get('view')); };
$container['notAllowedHandler'] = function ($c) {	return new \Containers\NotAllowedHandler($c->get('view')); };
//$container['phpErrorHandler']	= function ($c) {	return new \Containers\PhpErrorHandler($c->get('view')); };
//$container['errorHandler']	= function ($c) {	return new \Containers\ErrorHandler($c->get('view')); };

/////////////////////////////////////////////////////////////////////////////////////////////
// middlewares
//$app->add(new \Containers\Finalyse($container));

/////////////////////////////////////////////////////////////////////////////////////////////
// Routes 
$app->group('/auth', function () use ($app) {
	$app->get('/login', '\Containers\AuthContainer:loginPage')->setName('auth.login');
	$app->post('/login', '\Containers\AuthContainer:loginPost');
	$app->get('/signout', '\Containers\AuthContainer:signout')->setName('auth.signout');
});

$app->get('/',		'\HomePage:homePage')->setName('home');
$app->group('/packages', function () use ($app) {
	$app->get('',				'\PackagePage:packagesPage')->setName('packages.list');
	$app->get('/{id:[0-9]+}',		'\PackagePage:packageByIdPage')->setName('packages.byId');
	$app->get('/{str}',			'\PackagePage:packageByStrPage')->setName('packages.byStr');
	$app->get('/{str}/edit',		'\PackagePage:packageEditPage')->setName('packages.edit');
	$app->get('/{str}/download',		'\PackagePage:packageDownload')->setName('packages.download');
	$app->get('/{str}/{id:[0-9]+}/download','\PackagePage:packageVersionDownload')->setName('packages.download.version');
});
$app->group('/apps', function () use ($app) {
	$app->get('',				'\AppPage:appsPage')->setName('apps.list');
	$app->get('/{id:[0-9]+}',		'\AppPage:appByIdPage')->setName('apps.byId');
	$app->get('/category/{id:[0-9]+}',	'\AppPage:appsByCatPage')->setName('apps.byCat');
	$app->get('/{id:[0-9]+}/edit',		'\AppPage:appEditPage')->setName('apps.edit');
	$app->post('/{id:[0-9]+}/upload',	'\AppPage:screenshotPost')->setName('upload.screenshot');
});
$app->group('/upload', function () use ($app) {
	$app->get('',	'\UploadPage:uploadPage')->setName('upload');
	$app->post('',	'\UploadPage:uploadPost');
});

/////////////////////////////////////////////////////////////////////////////////////////////
// running
$app->run();
?>
