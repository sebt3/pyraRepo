<?php
namespace Containers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\Handlers\NotAllowed as NotAllowed; 
use \Slim\Views\Twig; 
use \Slim\Http\Body;

class NotAllowedHandler extends NotAllowed {
	private $view;

	public function __construct(Twig $view) { 
		$this->view = $view; 
	}

	public function __invoke(Request $request, Response $response, array $methods) {
        if ($request->getMethod() === 'OPTIONS') {
            $status = 200;
            $contentType = 'text/plain';
            $output = $this->renderPlainNotAllowedMessage($methods);
        } else {
            $status = 405;
            $contentType = $this->determineContentType($request);
            switch ($contentType) {
                case 'application/json':
                    $output = $this->renderJsonNotAllowedMessage($methods);
                    break;

                case 'text/xml':
                case 'application/xml':
                    $output = $this->renderXmlNotAllowedMessage($methods);
                    break;

                case 'text/html':
                default:
			$this->view->render($response, 'errors/405.twig');
			return $response->withStatus(405)->withHeader('Content-Type', 'text/html'); 
            }
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);
        $allow = implode(', ', $methods);

        return $response
                ->withStatus($status)
                ->withHeader('Content-type', $contentType)
                ->withHeader('Allow', $allow)
                ->withBody($body);
	}

}
