<?php


namespace AdminConfirmed\ArmourEffects;

use pocketmine\Player;
use pocketmine\scheduler\Task;

class CheckArmourTask extends Task
{

    /** @var Main */
    private $plugin;

    /** @var Player */
    private $player;

    /**
     * CheckArmourTask constructor.
     * @param Main $plugin
     * @param Player $player
     */
    public function __construct(Main $plugin, Player $player)
    {
        $this->plugin = $plugin;
        $this->player = $player;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        $player = $this->player;
        $plugin = $this->plugin;

        $armour = $plugin->getArmourEffects($player);

        if (!is_null($armour)) {
            $plugin->applyEffects($player, $armour);
            return;
        }

        $plugin->removeEffects($player);
    }
}