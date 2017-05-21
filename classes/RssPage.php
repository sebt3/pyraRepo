<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


class RssPage extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}
	protected function formatTimestampRss($ts) {
		$date = new DateTime();
		$date->setTimestamp(round($ts/1000));
		return $date->format('D, d M Y H:i:s T');
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Model
	private function getFeed($uri) {
		$_ = $this->trans;
		$ret = [];
		$s = $this->db->prepare('select p.str_id, c.cnt, p.name, v.version, v.timestamp, p.infos
  from package_versions v, dbpackages p, 
	(select count(*) as cnt, pv.dbp_id from package_versions pv group by pv.dbp_id) c
 where p.id=v.dbp_id
   and c.dbp_id=v.dbp_id
   and p.enabled=1
   and v.enabled=1
 order by v.timestamp desc limit 0,10');
		$s->execute();
		while($r = $s->fetch()) {
			$r['url'] = $uri->getScheme().'://'.$uri->getHost().$this->router->pathFor('packages.byStr', array('str'=> $r['str_id']));
			$r['date'] = date('r',$r['timestamp']);
			$ret[] = $r;
		}
		return $ret;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Page Controlers

	public function rssPage (Request $request, Response $response) {
		$resp = $this->view->render($response, 'rss.twig', [
			'list'	=> $this->getFeed($request->getUri())
 		]);
 		return $resp->withHeader('Content-Type', 'application/rss+xml');
	}

}

?>
