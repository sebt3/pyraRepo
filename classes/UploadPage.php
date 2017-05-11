<?php
use Interop\Container\ContainerInterface;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class UploadPage extends CorePage {
	public function __construct(ContainerInterface $ci) { 
		parent::__construct($ci);
	}

	private function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	} 
	private function moveAll($old, $new) {
		$files = array_diff(scandir($old), array('.','..'));
		foreach ($files as $file) {
			if (!is_dir("$old/$file")) {
				rename("$old/$file","$new/$file");
			}
		}
	} 
/////////////////////////////////////////////////////////////////////////////////////////////
// Model

	private function getCategoryId($cat) {
		$i = $this->db->prepare('insert into categories(name) values (:name) on duplicate key update name=:name');
		$i->bindParam(':name', $cat,  PDO::PARAM_STR);
		$i->execute();
		$s = $this->db->prepare('select id from categories where name=:name');
		$s->bindParam(':name', $cat,  PDO::PARAM_STR);
		$s->execute();
		$r = $s->fetch();
		return $r['id'];
	}
	private function havePackageStr($str) {
		$s = $this->db->prepare('select id from dbpackages where str_id=:str');
		$s->bindParam(':str', $str,  PDO::PARAM_STR);
		$s->execute();
		$r = $s->fetch();
		return $r['id'];
	}
	private function getPackageId($str, $name, $arch, $icon) {
		$i = $this->db->prepare('insert into dbpackages(str_id, name, arch_id, icon) values (:str, :name, :arch, :icon) on duplicate key update name=:name, arch_id=:arch, icon=:icon');
		$i->bindParam(':str',  $str,   PDO::PARAM_STR);
		$i->bindParam(':name', $name,  PDO::PARAM_STR);
		$i->bindParam(':arch', $arch,  PDO::PARAM_INT);
		$i->bindParam(':icon', $icon,  PDO::PARAM_STR);
		$i->execute();
		$s = $this->db->prepare('select id from dbpackages where str_id=:str');
		$s->bindParam(':str', $str,  PDO::PARAM_STR);
		$s->execute();
		$r = $s->fetch();
		$u = $this->auth->getUserId();
		$c = $this->db->prepare('insert into packages_maintainers(dbp_id, user_id) values(:dbp,:user) on duplicate key update user_id=:user');
		$c->bindParam(':dbp', $r['id'],  PDO::PARAM_INT);
		$c->bindParam(':user', $u,  PDO::PARAM_INT);
		$c->execute();
		return $r['id'];
	}
	private function getPackageVersion($dbp, $vers, $path, $sdep, $pdep) {
		$date = new DateTime();
		$i = $this->db->prepare('insert into package_versions(dbp_id, by_user, version, timestamp, path, sys_deps, pkg_deps) values (:dbp, :user, :vers, :ts, :path, :sdep, :pdep) on duplicate key update by_user=:user, version=:vers, timestamp=:ts, path=:path, sys_deps=:sdep, pkg_deps=:pdep');
		$u = $this->auth->getUserId();
		$ts = $date->getTimestamp();
		$i->bindParam(':dbp',  $dbp,   PDO::PARAM_INT);
		$i->bindParam(':user', $u,     PDO::PARAM_INT);
		$i->bindParam(':vers', $vers,  PDO::PARAM_STR);
		$i->bindParam(':ts',   $ts,    PDO::PARAM_INT);
		$i->bindParam(':path', $path,  PDO::PARAM_STR);
		$i->bindParam(':sdep', $sdep,  PDO::PARAM_STR);
		$i->bindParam(':pdep', $pdep,  PDO::PARAM_STR);
		$i->execute();
		$s = $this->db->prepare('select id from package_versions where dbp_id=:dbp and version=:vers');
		$s->bindParam(':dbp',  $dbp,   PDO::PARAM_INT);
		$s->bindParam(':vers', $vers,  PDO::PARAM_STR);
		$s->execute();
		$r = $s->fetch();
		return $r['id'];
	}
	
	private function addPackage($pack, $fname) {
		$_ = $this->trans;
		if (!is_array($pack)) {
			$this->flash->addMessage('error', $_('Parse failed: No "Package Entry" section found'));
			return 0;
		} else if (!isset($pack['Id'])) {
			$this->flash->addMessage('error', $_('Parse failed: No package "Id" found'));
			return 0;
		} else if (!isset($pack['Version'])) {
			$this->flash->addMessage('error', $_('Parse failed: No package "Version" found'));
			return 0;
		} else if (!isset($pack['Name'])) {
			$this->flash->addMessage('error', $_('Parse failed: No package "Name" found'));
			return 0;
		} else if (!isset($pack['Arch'])&&!isset($pack['arch'])) {
			$this->flash->addMessage('error', $_('Parse failed: No package "Arch" found'));
			return 0;
		} else if (strlen($pack['Id'])>31) {
			$this->flash->addMessage('error', $_('Parse failed: Package "Id" too long'));
			return 0;
		}

		$path = realpath(__DIR__.'/../dbps').DIRECTORY_SEPARATOR.$pack['Id'].DIRECTORY_SEPARATOR.$pack['Version'].DIRECTORY_SEPARATOR.$fname;
		$ico  = isset($pack['Icon'])?'/icons/'.$pack['Id'].'/'.$pack['Icon']:null;
		$a = $this->getArchId(isset($pack['Arch'])?$pack['Arch']:$pack['arch']);
		$p = $this->havePackageStr($pack['Id']);
		if ($p!=null) {
			if(!$this->isPackageMaintainer($p)) {
				$this->flash->addMessage('error', $_('You are not a defined maintainer'));
				return 0;
			}
		}else 
			$p = $this->getPackageId($pack['Id'], $pack['Name'], $a, $ico);
		$v = $this->getPackageVersion($p, $pack['Version'], $path, isset($pack['SysDependency'])?$pack['SysDependency']:null, isset($pack['PkgDependency'])?$pack['PkgDependency']:null);
		$i = $this->db->prepare('update dbpackages set last_vers=:vers where id=:id');
		$i->bindParam(':id',   $p,   PDO::PARAM_INT);
		$i->bindParam(':vers', $v,   PDO::PARAM_INT);
		$i->execute();

		// TODO: add support for ['Names'][] translations in package_names
		return $p;
	}

	private function addApp($id, $name, $app) {
		if (!is_array($app) || !isset($app['Type']) || $app['Type'] != 'Application' || !isset($app['Name']))
			return false;
		// TODO: add support for Names[] and Comments[] translations
		$comm = isset($app['Comment'])?$app['Comment']:null;
		$ico  = isset($app['Icon'])?'/icons/'.$name.'/'.$app['Icon']:null;
		$i = $this->db->prepare('insert into apps(dbp_id, name, comments, icon) values (:dbp, :name, :comm, :icon) on duplicate key update comments=:comm, icon=:icon');
		$i->bindParam(':dbp',  $id,   PDO::PARAM_INT);
		$i->bindParam(':name', $app['Name'],  PDO::PARAM_STR);
		$i->bindParam(':comm', $comm, PDO::PARAM_STR);
		$i->bindParam(':icon', $ico,  PDO::PARAM_STR);
		$i->execute();
		$s = $this->db->prepare('select id from apps where dbp_id=:dbp and name=:name');
		$s->bindParam(':dbp',  $id,   PDO::PARAM_INT);
		$s->bindParam(':name', $app['Name'],  PDO::PARAM_STR);
		$s->execute();
		$r = $s->fetch();
		if (isset($app['Categories'])) {
			$appid = $r['id'];
			foreach(explode(';', $app['Categories']) as $cat) {
				if ($cat == "") continue;
				$ci = $this->getCategoryId($cat);
				$c = $this->db->prepare('insert into apps_categories(cat_id, app_id) values (:cat, :app) on duplicate key update app_id=:app');
				$c->bindParam(':cat',  $ci,    PDO::PARAM_INT);
				$c->bindParam(':app',  $appid, PDO::PARAM_INT);
				$c->execute();
			}
		}
		return $r['id'];
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// DBP parsing
	private function parseDesktop($file) {
		$ret	 = [];
		$current = "";
		$lines	 = explode("\n", file_get_contents($file));
		foreach ($lines as $n => $line) {
			if (preg_match('/^\#/', $line) || preg_match('/^;/', $line))
				continue;
			if (preg_match("/^\[(.*)\]/", $line, $tmp)) {
				$current = $tmp[1];
				$ret[$current]			= [];
				$ret[$current]['Names']		= [];
				$ret[$current]['Comments']	= [];
				continue;
			}
			if ($current=="")
				continue; // No current section is bogus so skiping the line
			$cols = explode('=', $line);
			if (!preg_match('/\[/', $cols[0]))
				$ret[$current][$cols[0]] = $cols[1];
			else if (preg_match('/Name\[(.*)\]/', $cols[0], $tmp))
				$ret[$current]['Names'][$tmp[1]] = $cols[1];
			else if (preg_match('/Comment\[(.*)\]/', $cols[0], $tmp))
				$ret[$current]['Comments'][$tmp[1]] = $cols[1];
			else $this->logger->addError("parseDesktop: Dont know what to do with ".var_export($cols, true));
		}
		return $ret;
	}
	private function parseDBP($path) {
		$_ = $this->trans;
		// Extracting the appended zip file
		system('unzip -od '.$path.'.data '.$path.' >/dev/null 2>&1');
		$ret = [];
		// Find all .desktop files
		if(!is_dir($path.'.data')) {
			$this->flash->addMessage('error', $_('Extracting the metadata failed'));
			unlink($path);
			return $ret;
		}
		$c = 0;
		foreach (new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path.'.data')), '/^.+\.desktop$/i', RecursiveRegexIterator::GET_MATCH) as $file) {
			// parse the desktop files
			$f = $file[0];
			$k = basename($f);
			$ret[$k] = $this->parseDesktop($f);
			if(!is_array($ret[$k]) || count($ret[$k])<1)
				$this->flash->addMessage('warning', $_('Parsing ').$k.$_(' failed'));
			if(isset($ret[$k]['Package Entry']))
				$ret['Package Entry'] = $ret[$k]['Package Entry'];
			$c++;
		}
		if($c==0)
			$this->flash->addMessage('warning', $_('No .desktop file found'));
		return $ret;
	}

/////////////////////////////////////////////////////////////////////////////////////////////
// Page Controlers

	public function uploadPage (Request $request, Response $response) {
 		return $this->view->render($response, 'upload.twig', []);
	}
	public function uploadPost (Request $request, Response $response) {
		$_ = $this->trans;
		$this->auth->assertAuth($request, $response);			
		$files = $request->getUploadedFiles();
		$this->logger->addInfo("upload: ".var_export($files, true));
		$newfile = $files['dbpfile'];
		$filename = str_replace(' ', '_', $newfile->getClientFilename());
		
		if (empty($newfile))
			$this->flash->addMessage('error', $_('No File uploaded'));
		else if ($newfile->getError() === UPLOAD_ERR_OK) {
			$path = __DIR__.'/../dbps/upload/'.$filename;
			$newfile->moveTo($path);
			$parsed = $this->parseDBP(realpath($path));
			$this->logger->addWarning($id." ".var_export($parsed, true));
			if (isset($parsed['Package Entry'])) {
				$id = $this->addPackage($parsed['Package Entry'], $filename);
				if ($id != 0) {
					// Adding the apps
					foreach($parsed as $k => $app) {
						if($k=='Package Entry'||!isset($app['Desktop Entry']))
							continue;
						$this->addApp($id, $parsed['Package Entry']['Id'],$app['Desktop Entry']);
					}
					// Moving all the icons
					$icondir = realpath(__DIR__.'/../public/icons').DIRECTORY_SEPARATOR.$parsed['Package Entry']['Id'];
					if(!is_dir($icondir))
						mkdir($icondir);
					$this->moveAll(realpath($path).'.data'.DIRECTORY_SEPARATOR.'icons', $icondir);

					// Moving the package
					$target_dir = realpath(__DIR__.'/../dbps').DIRECTORY_SEPARATOR.$parsed['Package Entry']['Id'];
					if(!is_dir($target_dir))
						mkdir($target_dir);
					$target_ver = $target_dir.DIRECTORY_SEPARATOR.$parsed['Package Entry']['Version'];
					if(!is_dir($target_ver))
						mkdir($target_ver);
					$target = $target_ver.DIRECTORY_SEPARATOR.$filename;
					rename(realpath($path), $target);
					
					// delete the temporary data directory
					$this->delTree($path.'.data');
					//$this->flash->addMessage('info', ' '.$id);
					return $response->withRedirect($this->router->pathFor('packages.edit', array('str'=> $parsed['Package Entry']['Id'])));
				}
			} else
				$this->flash->addMessage('error', $_('No "Package Entry" found'));
		} else {
			$this->logger->addError("upload: ".var_export($newfile->getError(), true).' for '.var_export($newfile, true));
			$this->flash->addMessage('error', $_('Upload Failed'));
		}
 		return $response->withRedirect($this->router->pathFor('upload'));
	}
}

?>
