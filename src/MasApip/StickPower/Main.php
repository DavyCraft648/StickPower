<?php

namespace MasApip\StickPower;

use pocketmine\{Player, Server};
use pocketmine\command\{Command, CommandSender};
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\{Item, ItemFactory};
use pocketmine\level\{Explosion, Level, Position};
use pocketmine\math\{AxisAlignedBB, VoxelRayTrace, Vector3};
use pocketmine\network\mcpe\protocol\{PlaySoundPacket, AddActorPacket};
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

use jojoe77777\FormAPI\SimpleForm;
use slapper\entities\SlapperEntity;

class Main extends PluginBase implements Listener {
	
	public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label,array $args): bool{
		switch(strtolower($cmd->getName())){
			case "stickpower":
				if($sender instanceof Player){
					$form = new SimpleForm(function (Player $player, $data){
						$result = $data;
						if($result === null){
							return true;
						}
						switch($result){
							case 0:
								if(!($player->hasPermission("stickpower.lightning") or $player->hasPermission("stickpower.lightning.use"))) return;
								$player->sendMessage("§aKamu telah mengambil §3Stick §l§dLightning");
								$stick = ItemFactory::get(Item::STICK);
								$stick->setCustomName("§r§l§dLightning §r§7(Klik Kanan)");
								$player->getInventory()->addItem($stick);
							break;
							case 1:
								if(!($player->hasPermission("stickpower.teleport") or $player->hasPermission("stickpower.teleport.use"))) return;
								$player->sendMessage("§aKamu telah mengambil §3Stick §l§3Teleport");
								$stick = ItemFactory::get(Item::STICK);
								$stick->setCustomName("§r§l§3Teleport §r§7(Klik Kanan)");
								$player->getInventory()->addItem($stick);
							break;
							case 2:
								if(!($player->hasPermission("stickpower.explode") or  $player->hasPermission("stickpower.explode.use"))) return;
								$player->sendMessage("§aKamu telah mengambil §3Stick §l§cExplode");
								$stick = ItemFactory::get(Item::STICK);
								$stick->setCustomName("§r§l§cExplode §r§7(Klik Kanan)");
								$player->getInventory()->addItem($stick);
							break;
							case 3:
								if(!($player->hasPermission("stickpower.jumpboost") or $player->hasPermission("stickpower.jumpboost.use"))) return;
								$player->sendMessage("§aKamu telah mengambil §3Stick §l§1Jump Boost");
								$stick = ItemFactory::get(Item::STICK);
								$stick->setCustomName("§r§l§1Jump Boost §r§7(Klik Kanan)");
								$player->getInventory()->addItem($stick);
							break;
						}
					});					
					$form->setTitle("§l§6Stick §aPower");
					$form->setContent("§aKamu akan memiliki §dStick §adengan kekuatan tertentu!");
					if($sender->hasPermission("stickpower.lightning") or $sender->hasPermission("stickpower.lightning.use")){
						$form->addButton("Stick Lightning");
					}else{
						$form->addButton(" ");
					}
					if($sender->hasPermission("stickpower.teleport") or $sender->hasPermission("stickpower.teleport.use")){
						$form->addButton("Stick Teleport");
					}else{
						$form->addButton(" ");
					}
					if($sender->hasPermission("stickpower.explode") or  $sender->hasPermission("stickpower.explode.use")){
						$form->addButton("Stick Explode");
					}else{
						$form->addButton(" ");
					}
					if($sender->hasPermission("stickpower.jumpboost") or $sender->hasPermission("stickpower.jumpboost.use")){
						$form->addButton("Stick Jump Boost");
					}else{
						$form->addButton(" ");
					}
					$form->sendToPlayer($sender);
				}
			break;		
		}
		return true;
	}

	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$item = $event->getItem();
		$block = $event->getBlock();
		if($item->getCustomName() === "§r§l§dLightning §r§7(Klik Kanan)" and $item->getId() === Item::STICK){ // It's a stick right??
			if(!$player->hasPermission("stickpower.lightning.use")) { // To prevent Exploit
				$player->getInventory()->setItemInHand(ItemFactory::get(Item::AIR)); // Why not???
				$player->sendTip(TextFormat::RED . "You don't have permission to use this stick!");
				$player->sendMessage(TextFormat::RED . "Missing permission: stickpower.lightning.use");
				return;
			}
			if($block->getId() === 0) return; // Fixing Bug ???
			$pk = new PlaySoundPacket();
			$pk->soundName = "ambient.weather.thunder";
			$pk->volume = 300;
			$pk->pitch = 1;
			$pk->x = $block->getX();
			$pk->y = $block->getY();
			$pk->z = $block->getZ();
			Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
			$pk = new PlaySoundPacket();
			$pk->soundName = "ambient.weather.lightning.impact";
			$pk->volume = 100;
			$pk->pitch = 1;
			$pk->x = $block->getX();
			$pk->y = $block->getY();
			$pk->z = $block->getZ();
			Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
			$light = new AddActorPacket();
			$light->type = "minecraft:lightning_bolt"; // Fixing issue #1 ???
			$light->entityRuntimeId = Entity::$entityCount++;
			$light->metadata = array();
			$light->motion = null; 
			$light->yaw = $player->getYaw();
			$light->pitch = $player->getPitch();
			$light->position = new Vector3($block->getX(), $block->getY(), $block->getZ());
			Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $light);
			foreach($player->getLevel()->getNearbyEntities(new AxisAlignedBB($block->getFloorX() - ($radius = 5), $block->getFloorY() - $radius, $block->getFloorZ() - $radius, $block->getFloorX() + $radius, $block->getFloorY() + $radius, $block->getFloorZ() + $radius), $player) as $e){
				if($this->getServer()->getPluginManager()->getPlugin("Slapper") != null){ // Hmmph
					if($e instanceof SlapperEntity) return;
				}
				$e->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_MAGIC, 9));
			}
		}	

		if($item->getCustomName() === "§r§l§3Teleport §r§7(Klik Kanan)" and $item->getId() === Item::STICK){
			if(!$player->hasPermission("stickpower.teleport.use")) {
				$player->getInventory()->setItemInHand(ItemFactory::get(Item::AIR));
				$player->sendTip(TextFormat::RED . "You don't have permission to use this stick!");
				$player->sendMessage(TextFormat::RED . "Missing permission: stickpower.teleport.use");
				return;
			}
			$start = $player->add(0, $player->getEyeHeight(), 0);
			$end = $start->add($player->getDirectionVector()->multiply($player->getViewDistance() * 16));
			$level = $player->level;

			foreach(VoxelRayTrace::betweenPoints($start, $end) as $vector3){
				if($vector3->y >= Level::Y_MAX or $vector3->y <= 0){
					return;
				}

				if(($result = $level->getBlockAt($vector3->x, $vector3->y, $vector3->z)->calculateIntercept($start, $end)) !== null){
					$target = $result->hitVector;
					$player->teleport($target);
					return;
				}
			}
		}

		if($item->getCustomName() === "§r§l§cExplode §r§7(Klik Kanan)" and $item->getId() === Item::STICK){
			if(!$player->hasPermission("stickpower.explode.use")) {
				$player->getInventory()->setItemInHand(ItemFactory::get(Item::AIR));
				$player->sendTip(TextFormat::RED . "You don't have permission to use this stick!");
				$player->sendMessage(TextFormat::RED . "Missing permission: stickpower.explode.use");
				return;
			}
			$explosion = new Explosion(new Position($block->getX(), $block->getY(), $block->getZ(), $player->getLevel()), 1, null);
            $explosion->explodeB();
		}

		if($item->getCustomName() === "§r§l§1Jump Boost §r§7(Klik Kanan)" and $item->getId() === Item::STICK){
			if(!$player->hasPermission("stickpower.jumpboost.use")) {
				$player->getInventory()->setItemInHand(ItemFactory::get(Item::AIR));
				$player->sendTip(TextFormat::RED . "You don't have permission to use this stick!");
				$player->sendMessage(TextFormat::RED . "Missing permission: stickpower.jumpboost.use");
				return;
			}
			$player->setMotion(new Vector3(0, 3, 0));
		}
	}
}
