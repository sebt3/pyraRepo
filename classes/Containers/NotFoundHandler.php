<?php
namespace Containers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Handlers\NotFound as NotFound; 
use \Slim\Views\Twig as Twig;
use \Slim\Http\Body as Body;

class NotFoundHandler extends \Slim\Handlers\NotFound {
	private $view;

	public function __construct(Twig $view) { 
		$this->view = $view; 
	}

	public function __invoke(Request $request, Response $response) { 
		$contentType = $this->determineContentType($request);
		switch ($contentType) {
		case 'application/json':
			$output = $this->renderJsonNotFoundOutput();
			break;

		case 'text/xml':
		case 'application/xml':
			$output = $this->renderXmlNotFoundOutput();
			break;

		case 'text/html':
		default:
			$this->view->render($response, 'errors/404.twig');
			return $response->withStatus(404)->withHeader('Content-Type', 'text/html'); 
		}

		$body = new Body(fopen('php://temp', 'r+'));
		$body->write($output);

		return $response->withStatus(404)
				->withHeader('Content-Type', $contentType)
				->withBody($body);
	}

}

?>
