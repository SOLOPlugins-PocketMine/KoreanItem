<?php

namespace solo\koreanitem;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\UnknownBlock;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;

class KoreanItem extends PluginBase{

	const LIST_VERSION = 1;

	/** @var array */
	private $list;

	public function onEnable(){
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
		$block = BlockFactory::get($id, $meta);
		if(!$block instanceof Renameable){
			if($block instanceof UnknownBlock){
				$block = new Block($id, $meta);
			}
			$block = createRenameableBlock($block);
		}
		$block->rename($meta, $name);
		BlockFactory::registerBlock($block, true); // override
	}

	public function setItemName(int $id, int $meta, string $name){
		$item = ItemFactory::get($id, $meta);
		if(!$item instanceof Renameable){
			if($item instanceof Air){
				$item = new Item($id, $meta);
			}
			$item = createRenameableItem($item);
		}
		$item->rename($meta, $name);
		ItemFactory::registerItem($item, true); // override
	}

	// private function dump($obj){
	// 	$ref = new \ReflectionObject($obj);
	// 	echo PHP_EOL . "----- " . $ref->getShortName() . " -----" . PHP_EOL;
	// 	echo "class " . $ref->getName() . " extends " . $ref->getParentClass()->getName() . " implements " . implode($ref->getInterfaceNames(), " ") . PHP_EOL;
	// 	echo "Object name : " . $obj->getName() . PHP_EOL;
	// 	echo "...start dump..." . PHP_EOL;
	// 	var_dump($obj);
	// 	echo "...end dump..." . PHP_EOL;
	// 	echo "-----------------" . PHP_EOL;
	// }
}


interface Renameable{
	public function rename(int $meta, string $name);

	public function cloneFrom($obj);
}

function createRenameableInstance(string $class, $derived = null){
	$instance = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
	if($derived !== null) $instance->cloneFrom($derived);
	return $instance;
}

function createRenameableBlock(Block $block){
	$ref = (new \ReflectionObject($block));
	$class = "_KoreanBlock" . $ref->getShortName();
	try{ // class exists?
		return createRenameableInstance($class, $block);
	}catch(\ReflectionException $e){ }

	$derived = $ref->getName();
	$interface = Renameable::class;
	$code =
<<<EOF
class $class extends $derived implements $interface{
	private \$_names = [];

	public function rename(int \$meta, string \$name){
		\$this->_names[\$meta] = \$name;
	}

	public function getName() : string{
		return \$this->_names[\$this->meta] ?? \$this->_names[0] ?? parent::getName();
	}

	public function cloneFrom(\$block){
		foreach(get_object_vars(\$block) as \$k => \$v){
			\$this->\$k = \$v;
		}
	}
}
EOF;

	eval($code);
	return createRenameableInstance($class, $block);
}

function createRenameableItem(Item $item){
	$ref = (new \ReflectionObject($item));
	$class = "_KoreanItem" . $ref->getShortName();
	try{ // class exists?
		return createRenameableInstance($class, $item);
	}catch(\ReflectionException $e){ }

	$derived = $ref->getName();
	$interface = Renameable::class;
	$code =
<<<EOF
class $class extends $derived implements $interface{
	private \$_names = [];

	public function rename(int \$meta, string \$name){
		\$this->_names[\$meta] = \$name;
	}

	public function getVanillaName() : string{
		return \$this->_names[\$this->meta] ?? \$this->_names[0] ?? parent::getName();
	}

	public function cloneFrom(\$item){
		foreach(get_object_vars(\$item) as \$k => \$v){
			\$this->\$k = \$v;
		}
	}
}
EOF;

	eval($code);
	return createRenameableInstance($class, $item);
}