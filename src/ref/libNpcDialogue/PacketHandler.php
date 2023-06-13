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
			// REQUEST_SET_ACTIONS is received when the player modified the content of button
			// but we need more debugging to know the all types of actions
			// for now, we just handle the button changed
			if($requestType === NpcRequestPacket::REQUEST_SET_ACTIONS){
				$actionData = json_decode($packet->commandString, true, 512);
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
				// REQUEST_EXECUTE_ACTION is received when player clicked the button
				$buttonIndex = $packet->actionIndex;
				$npcDialogue->onButtonClicked($player, $buttonIndex);
			}elseif($requestType === NpcRequestPacket::REQUEST_SET_NAME){
				// REQUEST_SET_NAME is received when player tried to change the dialogue's name
				/*
				$newName = $packet->commandString;
				$npcDialogue->onSetNameRequested($newName);
				*/
				// TODO: Need debug
			}elseif($requestType === NpcRequestPacket::REQUEST_SET_INTERACTION_TEXT){
				// REQUEST_SET_INTERACTION_TEXT is received when player tried to modify the dialogue body
				// TODO
			}elseif($requestType === NpcRequestPacket::REQUEST_SET_SKIN){
				// REQUEST_SET_SKIN is received when player tried to change the skin of NPC
				// Currently we don't know what integer should be sent to change the NPC skin
				// TODO: we need to find out the types of skin
				/** @link NpcDialogue::sendTo() */
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