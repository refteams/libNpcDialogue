<?php

declare(strict_types=1);

namespace ref\libNpcDialogue\form;

use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;

final class NpcDialogueButtonData implements \JsonSerializable{

	public const TYPE_URL = 0; // works on only Minecraft Education Edition
	public const TYPE_COMMAND = 1;
	public const UNKNOWN = 2;

	// enables button
	public const MODE_BUTTON = 0;
	// those two are disable button, no idea what is this for
	public const MODE_ON_CLOSE = 1;
	public const MODE_ON_ENTER = 2;

	public const CMD_VER = 17; // 17 in 1.18.0.2.0, and 12 in 1.16.0.2.0

	protected string $name = "";

	protected string $text = "";

	protected ?array $data = []; // null when type is url

	protected int $mode = self::MODE_BUTTON;

	protected int $type = self::TYPE_COMMAND;

	protected bool $forceCloseOnClick = false;

	/**
	 * @var \Closure|null
	 * @phpstan-var \Closure(Player $player) : void
	 */
	protected ?\Closure $clickHandler = null;

	public static function create() : NpcDialogueButtonData{
		return new self;
	}

	public function setName(string $name) : self{
		$this->name = $name;
		return $this;
	}

	public function setText(string $text) : self{
		$this->text = $text;
		$this->data = array_map(static fn($str) => [
			"cmd_line" => $str,
			"cmd_ver" => self::CMD_VER
		], explode("\n", $text));
		return $this;
	}

	/**
	 * This doesn't work on normal Minecraft: Bedrock Edition
	 *
	 * @param string $link
	 *
	 * @return $this
	 */
	public function addLink(string $link) : self{
		$this->text = $link;
		$this->type = self::TYPE_URL;
		$this->data = null;
		return $this;
	}

	public function setMode(int $mode) : self{
		$this->mode = $mode;
		return $this;
	}

	public function setType(int $type) : self{
		$this->type = $type;
		return $this;
	}

	/**
	 * Forces client to close the form after clicking button
	 *
	 * @param bool $forceCloseOnClick
	 *
	 * @return $this
	 */
	public function setForceCloseOnClick(bool $forceCloseOnClick) : self{
		if($this->mode !== self::MODE_BUTTON){
			throw new AssumptionFailedError("Cannot set force close on click when mode is not button");
		}
		$this->forceCloseOnClick = $forceCloseOnClick;
		return $this;
	}

	/**
	 * @phpstan-param \Closure(Player $player) : void $clickHandler
	 */
	public function setClickHandler(\Closure $clickHandler) : self{
		Utils::validateCallableSignature(static function(Player $player) : void{}, $clickHandler);
		$this->clickHandler = $clickHandler;
		return $this;
	}

	/**
	 * @return \Closure|null
	 * @phpstan-return \Closure(Player $player) : void
	 */
	public function getClickHandler() : ?\Closure{
		return $this->clickHandler;
	}

	public function getForceCloseOnClick() : bool{
		return $this->forceCloseOnClick;
	}

	public function jsonSerialize() : array{
		return [
			"button_name" => $this->name,
			"text" => $this->text,
			"data" => $this->data,
			"mode" => $this->mode,
			"type" => $this->type
		];
	}
}