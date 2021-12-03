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
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;
use ref\libNpcDialogue\form\NpcDialogueButtonData;
use function array_map;
use function json_encode;
use function trim;

final class NpcDialogue{

	protected ?int $fakeActorId = null;

	/** @var NpcDialogueButtonData[] */
	protected array $buttonData = [];

	/**
	 * It is the identifier for the dialogue
	 * Without this, we cannot handle the NpcRequestPacket properly
	 * It is why we force the user to provide this
	 */
	protected string $sceneName = "";

	protected string $npcName = "";

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
			$this->fakeActorId = Entity::nextRuntimeId();
			$player->getNetworkSession()->sendDataPacket(
				AddActorPacket::create(
					$this->fakeActorId,
					$this->fakeActorId,
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
			$propertyManager = $entity->getNetworkProperties();
			$propertyManager->setByte(EntityMetadataProperties::HAS_NPC_COMPONENT, 1);
			$propertyManager->setString(EntityMetadataProperties::INTERACTIVE_TAG, $this->dialogueBody);
			$propertyManager->setString(EntityMetadataProperties::NPC_ACTIONS, $mappedActions);
		}
		$pk = NpcDialoguePacket::create(
			$entity?->getId() ?? $this->fakeActorId,
			NpcDialoguePacket::ACTION_OPEN,
			$this->dialogueBody,
			$this->sceneName,
			$this->npcName,
			$mappedActions
		);
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function onButtonsChanged(array $buttons) : void{
		// TODO
	}

	public function addButton(NpcDialogueButtonData $buttonData) : void{
		$this->buttonData[] = $buttonData;
	}

	public function onDispose(Player $player) : void{
		if($this->fakeActorId !== null){
			$player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($this->fakeActorId));
			$this->fakeActorId = null;
		}
	}
}