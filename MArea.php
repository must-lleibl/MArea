<?php

/*
__PocketMine Plugin__
name=MArea
description=Just the best world protection you ever seen
version=private plugin editing p60
author=must and PEMapModder
class=MAreaPg
apiversion=12,13
*/

/*
Here is the progress record:
p15:	Created MArea class and permission checker TODO split the in-area/in-space checker into an independent function
		To decide: area (2D) or space (3D)
p57:	Guranteed customization!
		Optimized I/O efficiency
p60:	Player moving
		TODO: Make language customizable
*/


class MAreaPg implements Plugin {
	public static $instance = false;
	public static request() {
		return self::$instance;
	}
	public $a, $c;
	public $areas = array();
	public $config = false;
	public $selections = array();
	public $secPos = array();
	public function __construct(ServerAPI $api, $s = 0) {
		$this->a=$api;
		$this->c=$api->console;
	}
	public function init() {
		$noExt=$this->a->plugin->configPath($this)."settings.";
		$ext="yml";
		if(file_exists($noExt."txt"))
			$ext="txt";
		$this->config = new Config($noExt.$ext, CONFIG_YAML, array(
				"MArea main command" => "marea", // at ConsoleAPI.php trim(strtolower($cmd)) is used
				"alias" => array(
						"ma"
				),
		));
		$this->c->register($this->config->get("MArea main command"), "<help|subcmd> MArea main command", array($this, "cmds"));
		foreach($this->config->get("alias") as $alias)
			$this->c->alias($alias, $this->config->get("MArea main command"));
		$this->initMAreas();
		$this->a->addHandler("player.move", array($this, "logPos"), 1);
	}
	public function initMAreas(){
		$path=$this->a->plugin->configPath($this)."MAreas/";
		@mkdir($path);
		$dir = dir($path);
		while(($f = $dir->read()) !== false){
			if(substr($f, 0, 6) == "MArea-" and substr($f, -4) == ".yml")
				$this->areas[] = new MArea($path.$file);
		}
	}
	public function logPos(Entity $entity){
		if($entity->class !== ENTITY_PLAYER)
			return;
		$player = $entity->player;
		$this->secPos[strtolower("$player")] = $entity;
	}
	public function getLastSecPos($player){
		return $this->secPos[strtolower("$player")];
	}
	public function __destruct() {
	}
	public final function checkPermPlus(){
		foreach($this->a->plugin->getList() as $p){
			if($p["name"] == "PermissionPlus" and $p["author"] == "Omattyao")
				return true;
		}
		return false;
	}
}
class MArea {
	const FLAG_NONE = 0x00; // disallow all block touching only
	const FLAG_ENTER_FREE = 1; // FLAG_NONE and disallow anyone entering
	const FLAG_PVP_FREE = 2; // FLAG_NONE and disallow anyone in this area to attack, or disallow anyone to attack people in this area
	const FLAG_FURNACE_ALLOW = 16; // FLAG_NONE but allow using furnaces
	const FLAG_CHEST_ALLOW = 32; // FLAG_NONE but allow using chests
	const FLAG_CONTAINER_ALLOW = FLAG_FURNACE_ALLOW | FLAG_CHEST_ALLOW; // FLAG_NONE but (FLAG_FURNACE_ALLOW and FLAG_CHEST_ALLOW)
	const FLAG_ALL = 16384; // largest all-1 int // all flags enabled
	private $external = false;
	public $owner;
	public $owners = array();
	public $flag = self::FLAG_NONE; // planned to use like flags like E_ERROR and SORT_FLAG_CASE
	public $start, $end;
	public $insiders = array();
	public $id;
	public $name;
	public function __construct($start, $end = false, $owner = false, $name = false) {
		if($end === false and $owner === false){ // import from file
			if(!is_string($start))
				throw new Exception("Illegal constructor arguments for class MArea. MArea(Position \$start, Position \$end, Player \$owner) or MArea(string \$filepath) expected.");
			if(!is_file($start))
				throw new Exception("Illegal constructor arguments for class MArea. Argument 1 for constructing MArea from file must be file path, $start (not a file) given.");
			$this->external = new Config($start, CONFIG_YAML);
			$this->owners = $this->external->get("owners");
		}
		else{ // create new and save to file
			$this->start = $start;
			$this->end = $end;
			$this->owner = $owner;
			$this->owners[] = strtolower($owner->username);
			if($name === false){
				$pg = MAreaPg::request();
				$path = $pg->a->configPath($pg)."MAreas/";
				$id = 0;
				while(file_exists($path."MArea-$id.yml")){
					$id++;
				}
				$this->external = new Config($path."MArea-$id.yml", CONFIG_YAML, array(
						"owners" => $this->owners,
						"position-start" => array($start->x, $start->y, $start->z, $start->level->getName()),
						"position-end" => array($end->x, $end->y, $end->z, $end->level->getName()),,
						"creator" => $owner->username,
						"flags" => 0,
				));
			}
		}
		ServerAPI::request()->addHandler("player.block.touch", array($this, "touchCheck"));
		ServerAPI::request()->addHandler("player.block.place", array($this, "touchCheck"));
		ServerAPI::request()->addHandler("player.move", array($this, "moveCheck"));
		ServerAPI::request()->schedule(1200, array($this, "save"), array(), true);
	}
	public function touchCheck($data) {
		if(in_array(strtolower($data["player"]), $this->owners))
			return;
		$t = $data["target"];
		if($this->checkInside($t)){
			$data["player"]->sendChat("Well, I don't think you have permission to edit this area.");
			return false;
		}
	}
	public function moveCheck($data){
		if($data->class !== ENTITY_PLAYER)
			return;
		if(($this->flag & FLAG_ENTER_FREE) === 0)
			return;
		if($this->checkInside($t) === false)
			return;
		$data->player->teleport(MArea::request()->getLastSecPos($data->player));
		$data->player->sendChat("Well, I don't think you have permission to enter this area.");
	}
	public function addOwner(Player $player){
		$this->owners[] = strtolower($player->username); // well yes I should use $player->iusername but I am not sure
	}
	public function checkInside(Position $t){
		if($t->level->getName() != $t->level->getName())
			return false;
		if($t->x >= min($this->start->x, $this->end->x)
				and $t->x <= max($this->start->x, $this->end->x)
				// and $t->y >= min($this->start->y, $this->end->y)
				// and $t->y <= max($this->start->y, $this->end->y)
				and $t->z >= min($this->start->z, $this->end->z)
				and $t->z <= max($this->start->z, $this->end->z)) {
			return true;
		}
		return false;
	}
	public function __destruct(){
		$this->save();
	}
	public function __unset(){
		$this->save();
	}
	public function save(){
		$this->external->setAll(array(
				"owners" => $this->owners,
				"position-start" => array($start->x, $start->y, $start->z, $start->level->getName()),
				"position-end" => array($end->x, $end->y, $end->z, $end->level->getName()),,
				"creator" => $this->owner->iusername,
				"flags" => $this->flag,
		));
	}
	// FEATURE permision to enter the area/space
	// TODO decide how to make the permissions
}
