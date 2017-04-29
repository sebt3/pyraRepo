<?php
use Interop\Container\ContainerInterface;

class CorePage extends core {
	protected function getArchId($name) {
		$i = $this->db->prepare('insert into archs(name) values (:name) on duplicate key update name=:name');
		$i->bindParam(':name', $name,  PDO::PARAM_STR);
		$i->execute();
		$s = $this->db->prepare('select id from archs where name=:name');
		$s->bindParam(':name', $name,  PDO::PARAM_STR);
		$s->execute();
		$r = $s->fetch();
		return $r['id'];
	}
	protected function getLicenses() {
		$ret = [];
		$s = $this->db->prepare('select id,name from license_types order by id asc');
		$s->execute();
		while($r = $s->fetch()) {
			$ret[] = $r;
		}
		return $ret;
	}
	protected function formatTimestamp($ts) {
		$date = new DateTime();
		$date->setTimestamp(round($ts/1000));
		return $date->format('Y-m-d H:i:s');

	}
	protected function isPackageMaintainer($id) {
		return $this->auth->isPackageMaintainer($id);
	}
}

?>
