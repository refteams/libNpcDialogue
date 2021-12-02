<?php

declare(strict_types=1);

namespace ref\libNpcDialogue;

use pocketmine\plugin\PluginBase;

final class libNpcDialogue{

	private static ?PluginBase $plugin = null;

	public static function isRegistered() : bool{
		return self::$plugin !== null && self::$plugin->isEnabled();
	}

	public static function register(PluginBase $plugin) : void{
		if(self::$plugin !== null){
			throw new \RuntimeException("Plugin is already registered");
		}
		self::$plugin = $plugin;
		self::$plugin->getServer()->getPluginManager()->registerEvents(new PacketHandler(), self::$plugin);
	}
}