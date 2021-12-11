# libNpcDialogue

A virion that implements various npc dialogue for PocketMine-MP.

# API

```php
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
```

# Test Plugin

[tests/DialogueTest](./tests/DialogueTest.php)

# Note
This virion is for PocketMine-MP 4.x