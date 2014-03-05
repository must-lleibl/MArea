<?php

/*
__PocketMine Plugin__
name=MArea
description=Just the best world protection you ever seen
version=private plugin editing p15
author=must and xktiverz
class=MAreaPg
apiversion=12,13
*/

/*
//in line *version* will have to write in what percent will progress goes
Here is the progress record:
Created MArea class and permission checker TODO split the in-area/in-space checker into an independent function
To decide: area (2D) or space (3D)
*/


class MAreaPg implements Plugin {
	public static $instance = false;
	public static &request() {
		return self::$instance;
	}
	public $areas = array();
	public function __construct(ServerAPI $api, $s = 0) {
	}
	public function init() {
	}
	public function __destruct() {
	}
}
class MArea {
	public $owner;
	public $owners = array();
	public $flag = 0; // planned to use like flags like E_ERROR and SORT_FLAG_CASE
	public $start, $end;
	public function __construct(Position $start, Position $end, Player $owner) {
		$this->start = $start;
		$this->end = $end;
		$this->owner = $owner;
		ServerAPI::request()->addHandler("player.block.touch", array($this, "permissionCheck"));
	}
	public function permissionCheck($data, $event) {
		// flags
		if(in_array(strtolower($data["player"]), $this->owners))
			return;
		$t = $data["target"];
		if($t->level->getName() != $t->level->getName())
			return;
		if($t->x >= min($this->start->x, $this->end->x)
				and $t->x <= max($this->start->x, $this->end->x)
				// and $t->y >= min($this->start->y, $this->end->y)
				// and $t->y <= max($this->start->y, $this->end->y)
				and $t->z >= min($this->start->z, $this->end->z)
				and $t->z <= max($this->start->z, $this->end->z)) {
			// TODO configs (lang) and flags
		}
		else return;
	}
	// TODO add owner/editor functions
	// FEATURE permision to enter the area/space
}
