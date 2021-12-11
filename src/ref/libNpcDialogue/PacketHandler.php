<?php

declare(strict_types=1);

namespace ref\libNpcDialogue;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\NpcRequestPacket;
use pocketmine\utils\AssumptionFailedError;
use ref\libNpcDialogue\form\NpcDialogueButtonData;
use function json_decode;

final class PacketHandler implements Listener{

	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof NpcRequestPacket){
			$event->cancel(); // prevent console from spamming
			$requestType = $packet->requestType;
			$player = $event->getOrigin()->getPlayer() ?: throw new AssumptionFailedError("This packet cannot be received when player is not connected");
			$npcDialogue = DialogueStore::$dialogueQueue[$player->getName()][$packet->sceneName] ?? null;
			if($npcDialogue === null){
				return;
			}
			if($requestType === NpcRequestPacket::REQUEST_SET_ACTIONS){
				$actionData = json_decode($packet->commandString, true, 512, JSON_THROW_ON_ERROR);
				$buttons = [];
				foreach($actionData as $key => $actionDatum){
					$button = NpcDialogueButtonData::create()
						->setName($actionDatum["button_name"])
						->setText($actionDatum["text"])
						->setMode($actionDatum["mode"])
						->setType($actionDatum["type"]);
					$buttons[] = $button;
				}
				$npcDialogue->onButtonsChanged($buttons);
			}elseif($requestType === NpcRequestPacket::REQUEST_EXECUTE_ACTION){
				$buttonIndex = $packet->actionIndex;
				$npcDialogue->onButtonClicked($player, $buttonIndex);
			}elseif($requestType === NpcRequestPacket::REQUEST_SET_NAME){
				/*
				$newName = $packet->commandString;
				$npcDialogue->onSetNameRequested($newName);
				*/
				// TODO: Need debug
			}elseif($requestType === NpcRequestPacket::REQUEST_SET_INTERACTION_TEXT){
				// TODO
			}elseif($requestType === NpcRequestPacket::REQUEST_SET_SKIN){
				// TODO
			}
		}
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if(isset(DialogueStore::$dialogueQueue[$player->getName()])){
			foreach(DialogueStore::$dialogueQueue[$player->getName()] as $sceneName => $dialogue){
				$dialogue->onClose($player);
			}
			unset(DialogueStore::$dialogueQueue[$player->getName()]);
		}
	}
}