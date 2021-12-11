<?php

/**
 * @name DialogueTest
 * @author alvin0319
 * @main alvin0319\DialogueTest\DialogueTest
 * @version 1.0.0
 * @api 4.0.0
 */

declare(strict_types=1);

namespace alvin0319\DialogueTest;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use ref\libNpcDialogue\form\NpcDialogueButtonData;
use ref\libNpcDialogue\libNpcDialogue;
use ref\libNpcDialogue\NpcDialogue;

final class DialogueTest extends PluginBase{

	public function onEnable() : void{
		if(!libNpcDialogue::isRegistered()){
			libNpcDialogue::register($this);
		}
		$this->getServer()->getCommandMap()->register("test", new class extends Command{

			public function __construct(){
				parent::__construct("npc", "npc test");
			}

			public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
				if(!$sender instanceof Player){
					return;
				}
				$npcDialogue = new NpcDialogue();
				$npcDialogue->addButton(NpcDialogueButtonData::create()
					->setName("Test")
					->setText("Test123")
					->setClickHandler(function(Player $player) : void{
						$player->sendMessage("Hello world!");
					})
					->setForceCloseOnClick(true)
				);
				$npcDialogue->setNpcName("Test NPC");
				$npcDialogue->setDialogueBody("Test Body");
				$npcDialogue->setSceneName("TEST");
				$npcDialogue->sendTo($sender);
			}
		});
	}
}