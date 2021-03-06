<?php
function sortByName($collection){
	$return = array();
	$tmp = array();
	foreach($collection as $k=>$v){
		$tmp[$k] = $v->getName();
	}
	asort($tmp);
	foreach($tmp as $k=>$v){
		$return[] = $collection[$k];
	}

	return $return;
}
class Category{
	const PREFIX='./data/';

	var $parent;

	private $_path = '';
	public function __construct($path){
		$this->_path = $path;
		$parent_path = preg_replace('@/[^/]+$@', '', $path);
		if($parent_path != $path and $parent_path)
			$this->parent = new Category($parent_path);
	}

	public static function getSub($path=''){
		if($path)
			$path .= '/';
		$dirs =  glob(self::PREFIX.$path.'*', GLOB_ONLYDIR);
		if(!$dirs)
			return array();
		foreach ($dirs as $k=>$v){
			$v = str_replace(self::PREFIX,'',$v);
			$dirs[$k]=Category::get($v);
		}

		return sortByName($dirs);
	}

	public static function isValidPath($path){
		if(!preg_match('/^[a-z_0-9\.\/-]+$/i',$path))
			return false;
		if(is_dir(self::PREFIX.$path))
			return true;
	}

	public static function get($path){
		return new Category($path);
	}

	public function getName(){
		return $this->name();
	}

	function syntax(){
		if($this->meta('ft'))
			return $this->meta('ft');

		if($this->parent)
			if($this->parent->meta('ft'))
				return $this->parent->meta('ft');
	}

	function name(){
		return ($this->meta('name') ? $this->meta('name') : basename($this->_path));
	}

	function keywords(){
		$parent_kw = $this->parent ? $this->parent->keywords() : array();
		$this_kw = array($this->name().' примеры',
				 $this->name().' usage',
				 $this->name().' example');
		$meta_kw = (array)$this->meta('keywords');
		$return = array_merge($parent_kw, $this_kw, $meta_kw);
		array_unique($return);
		return $return;
	}

	public function getKeywords(){
		return $this->keywords();
		$key_file = self::PREFIX.$this->_path.'/keywords';
		if(file_exists($key_file))
			return trim(file_get_contents($key_file));
		$name = $this->getName();
		$keywords[]= $name.' примеры';
		$keywords[]= $name.' usage';
		$keywords[]= $name.' example';
		return implode(', ',$keywords);
	}
	public function getTitle(){
		return ($this->meta('title') ? $this->meta('title') : sprintf("Примеры: %s\n", $this->name()));
	}
	public function getHref(){
		return '/'.$this->_path;
	}
	public function getSubDirs(){
		return self::getSub($this->_path);
	}
	public function getLinks(){
		$us = $this->getUsage();
		$return = array();
		foreach($us as $v)
			if($v->link())
				$return[$v->id()] = $v->link();
		return $return;
	}

	public function getAuthors(){
		$us = $this->getUsage();
		$return = array();
		foreach($us as $v)
			if($v->prop('author'))
				$return[$v->id()] = $v->prop('author');
		return $return;
	}

	public function getUsage(){
		$us = ordered_exampls($this->_path);
		$return = array();
		foreach ($us as $v)
			$return[] = find_example(str_replace('./data/', '', $v));
		return $return;
	}

	public function getInfo(){
		return $this->meta('info');
	}

	var $_meta;
	function meta($key = null){
		if(is_null($this->_meta)){
			$pth = data($this->_path.'/meta.yaml');
			if(file_exists($pth))
				$this->_meta = unyaml($pth);
		}
		if(is_null($key))
			return $this->_meta;

		if(isset($this->_meta[$key]))
			return $this->_meta[$key];
	}
}
