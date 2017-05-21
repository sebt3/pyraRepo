<?php
namespace Containers;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface as Container;

class Finalyse extends \core {

/////////////////////////////////////////////////////////////////////////////////////////////
// middleware
	public function __invoke(Request $request, Response $response, callable $next) {
		$response = $next($request, $response);
		// populate empty error pages
		if (404 === $response->getStatusCode() && 0 === $response->getBody()->getSize())
			return $this->ci['notFoundHandler']($request, $response);
		if (405 === $response->getStatusCode() && 0 === $response->getBody()->getSize())
			return $this->ci['notAllowedHandler']($request, $response, array());
		if (500 === $response->getStatusCode() && 0 === $response->getBody()->getSize())
			return $this->ci['errorHandler']($request, $response, new Exception('Generated error'));
		//return $response;

		// Minify HTML
		if (! $response->hasHeader('Content-type'))
			return $response;
		$a = explode ( ';', $response->getHeaderLine('Content-type'));
		switch ($a[0]) {
		case 'text/html':
			$content = (string)$response->getBody();
			$search = array(
				'/\>[^\S ]+/s',
				'/[^\S ]+\</s',
				'/\>[\r\n\t ]+\</s',
				'/(\s)+/s',
				'/\n/',
				'/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s',
			);
			$replace = array(
				'>',
				'<',
				'><',
				'\\1',
				' ',
				''
			);
			$newContent = preg_replace($search, $replace, $content);
			$newBody = new \Slim\Http\Body(fopen('php://temp', 'r+'));
			$newBody->write($newContent);
			return $response->withBody($newBody);
		default:
			return $response;
		}
	}

}
