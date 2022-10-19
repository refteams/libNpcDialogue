<?php

declare(strict_types=1);

namespace ref\libNpcDialogue;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\NpcDialoguePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;
use ref\libNpcDialogue\event\DialogueNameChangeEvent;
use ref\libNpcDialogue\form\NpcDialogueButtonData;
use function array_key_exists;
use function array_map;
use function json_encode;
use function trim;

final class NpcDialogue{

	protected ?int $actorId = null;

	protected bool $fakeActor = true;

	/** @var NpcDialogueButtonData[] */
	protected array $buttonData = [];

	/**
	 * sceneName is used for identifying the dialogue.
	 * Without this, We cannot handle NpcRequestPacket properly.
	 */
	protected string $sceneName = "";

	/**
	 * npcName is used for a title of dialogue
	 */
	protected string $npcName = "";

	/**
	 * dialogueBody is used for body of dialogue
	 */
	protected string $dialogueBody = "";

	public function setSceneName(string $sceneName) : void{
		if(trim($sceneName) === ""){
			throw new \InvalidArgumentException("Scene name cannot be empty");
		}
		$this->sceneName = $sceneName;
	}

	public function setNpcName(string $npcName) : void{
		$this->npcName = $npcName;
	}

	public function setDialogueBody(string $dialogueBody) : void{
		$this->dialogueBody = $dialogueBody;
	}

	public function sendTo(Player $player, ?Entity $entity = null) : void{
		if(trim($this->sceneName) === ""){
			throw new \InvalidArgumentException("Scene name cannot be empty");
		}
		$mappedActions = json_encode(array_map(static fn(NpcDialogueButtonData $data) => $data->jsonSerialize(), $this->buttonData), JSON_THROW_ON_ERROR);
		if($entity === null){
			$this->actorId = Entity::nextRuntimeId();
			$player->getNetworkSession()->sendDataPacket(
				AddActorPacket::create(
					$this->actorId,
					$this->actorId,
					EntityIds::NPC,
					$player->getPosition()->add(0, 10, 0),
					null,
					$player->getLocation()->getPitch(),
					$player->getLocation()->getYaw(),
					$player->getLocation()->getYaw(),
					[],
					[
						EntityMetadataProperties::HAS_NPC_COMPONENT => new ByteMetadataProperty(1),
						EntityMetadataProperties::INTERACTIVE_TAG => new StringMetadataProperty($this->dialogueBody),
						EntityMetadataProperties::NPC_ACTIONS => new StringMetadataProperty($mappedActions),
						// EntityMetadataProperties::VARIANT => new IntMetadataProperty(0), // Variant affects NPC skin
					],
					[]
				)
			);
		}else{
			$this->actorId = $entity->getId();
			$this->fakeActor = false;
			$propertyManager = $entity->getNetworkProperties();
			$propertyManager->setByte(EntityMetadataProperties::HAS_NPC_COMPONENT, 1);
			$propertyManager->setString(EntityMetadataProperties::INTERACTIVE_TAG, $this->dialogueBody);
			$propertyManager->setString(EntityMetadataProperties::NPC_ACTIONS, $mappedActions);
		}
		$pk = NpcDialoguePacket::create(
			$this->actorId,
			NpcDialoguePacket::ACTION_OPEN,
			$this->dialogueBody,
			$this->sceneName,
			$this->npcName,
			$mappedActions
		);
		$player->getNetworkSession()->sendDataPacket($pk);

		DialogueStore::$dialogueQueue[$player->getName()][$this->sceneName] = $this;
	}

	public function onButtonsChanged(array $buttons) : void{
		// TODO
	}

	public function onClose(Player $player) : void{
		$mappedActions = json_encode(array_map(static fn(NpcDialogueButtonData $data) => $data->jsonSerialize(), $this->buttonData), JSON_THROW_ON_ERROR);
		$player->getNetworkSession()->sendDataPacket(
			NpcDialoguePacket::create(
				$this->actorId,
				NpcDialoguePacket::ACTION_CLOSE,
				$this->dialogueBody,
				$this->sceneName,
				$this->npcName,
				$mappedActions
			)
		);
	}

	public function onButtonClicked(Player $player, int $buttonId) : void{
		if(!array_key_exists($buttonId, $this->buttonData)){
			throw new \InvalidArgumentException("Button ID $buttonId does not exist");
		}
		$button = $this->buttonData[$buttonId];

		if($button->getForceCloseOnClick()){
			$this->onClose($player);
		}

		($this->buttonData[$buttonId]->getClickHandler())($player);
	}

	public function onSetNameRequested(string $newName) : void{
		$ev = new DialogueNameChangeEvent($this, $this->npcName, $newName);
		$ev->call();
		if($ev->isCancelled()){
			return;
		}
		$this->npcName = $ev->getNewName();
	}

	public function addButton(NpcDialogueButtonData $buttonData) : void{
		$this->buttonData[] = $buttonData;
	}

	public function onDispose(Player $player) : void{
		if($this->actorId !== null && $this->fakeActor){
			$player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($this->actorId));
			$this->actorId = null;
		}
	}
}
