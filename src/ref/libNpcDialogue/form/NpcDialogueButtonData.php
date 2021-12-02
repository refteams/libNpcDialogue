<?php

declare(strict_types=1);

namespace ref\libNpcDialogue\form;

final class NpcDialogueButtonData implements \JsonSerializable{

	public const TYPE_URL = 0;
	public const TYPE_COMMAND = 1;
	public const UNKNOWN = 2;

	public const MODE_BUTTON = 0;
	public const MODE_ON_CLOSE = 1;

	protected string $name = "";

	protected string $text = "";

	protected $data = null;

	protected int $mode = self::MODE_BUTTON;

	protected int $type = self::TYPE_COMMAND;

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
		return $this;
	}

	public function addLink(string $link) : self{
		// TODO
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
	 * @phpstan-param \Closure(Player $player) : void
	 */
	public function setClickHandler(\Closure $clickHandler) : self{
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

	public function jsonSerialize() : array{
		return [
			"button_name" => $this->name,
			"text" => $this->text,
			"data" => $this->data, // TODO: WTF mojang
			"mode" => $this->mode,
			"type" => $this->type
		];
	}
}