<?php

namespace solo\koreanitem;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\plugin\PluginBase;

class KoreanItem extends PluginBase{

	const LIST_VERSION = 2;

	/** @var array */
	protected array $list;

	public function onEnable() : void{
		@mkdir($this->getDataFolder());

		$this->saveResource("list.json");
		$this->list = json_decode(file_get_contents($this->getDataFolder() . "list.json"), true);
		if($this->list["version"] < self::LIST_VERSION){
			$this->getLogger()->notice("새 아이템명 데이터를 감지하였습니다.");
			$this->saveResource("list.json", true);
			$this->list = json_decode(file_get_contents($this->getDataFolder() . "list.json"), true);
			$this->getLogger()->notice("성공적으로 업데이트하였습니다.");
		}
		$this->list = $this->list["data"];

		foreach($this->list as $id_meta => $name){
			$token = explode(":", $id_meta);
			$id = intval($token[0]);
			$meta = intval($token[1]);

			// 0 ~ 255 : Block
			if($id < 256) $this->setBlockName($id, $meta, $name);

			// else Item
			else $this->setItemName($id, $meta, $name);
		}
	}

	public function setBlockName(int $id, int $meta, string $name){
		$block = BlockFactory::getInstance()->get($id, $meta);
		if($block instanceof Block){
		    $block = new Block($block->getIdInfo(), $name, $block->getBreakInfo());
            BlockFactory::getInstance()->register($block, true); // override
		}
	}

	public function setItemName(int $id, int $meta, string $name){
		$item = ItemFactory::getInstance()->get($id, $meta);
		if($item instanceof Item){
		    $item = new Item(new ItemIdentifier($item->getId(), $item->getMeta()), $name);
            ItemFactory::getInstance()->register($item, true); // override
		}
	}

}