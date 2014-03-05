<?php

/*
__PocketMine Plugin__
name=MArea
description=Just the best world protection you ever seen
version=private plugin editing p1
author=must and xktiverz
class=MAreaPlugin
apiversion=12,13
*/

/*
//in line *version* will have to write in what percent will progres goes\\
//next version like=private plugin editing p2(if you editing the code just change the version) \\
*/

/*
LETS GET STARTED THEN
*/


class MAreaPlugin implements Plugin{
	public function __construct(ServerAPI $api, $s = 0) {
	}
	public function init() {
	}
	public function __destruct() {
	}
}
class MArea {
	public $owner;
	public $coOwners = array();
	public $flag;
	public function __construct(Position $start, Position $end, Player $owner) {
		
	}
}