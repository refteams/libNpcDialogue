<?php

namespace ref\libNpcDialogue\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use ref\libNpcDialogue\NpcDialogue;

final class DialogueNameChangeEvent extends BaseDialogueEvent implements Cancellable{
	use CancellableTrait;

	public function __construct(NpcDialogue $dialogue, protected string $oldName, protected string $newName){ parent::__construct($dialogue); }

	public function getOldName() : string{ return $this->oldName; }

	public function getNewName() : string{ return $this->newName; }

	public function setNewName(string $newName) : void{ $this->newName = $newName; }
}