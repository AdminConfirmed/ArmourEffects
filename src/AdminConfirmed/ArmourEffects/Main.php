<?php


namespace AdminConfirmed\ArmourEffects;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener
{

    /** @var Config */
    private $config;

    /** @var array */
    private $players = [];

    public function onEnable()
    {
        $this->saveResource('config.yml');
        $this->config = new Config($this->getDataFolder() . 'config.yml', Config::YAML);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }


    /**
     * @param EntityArmorChangeEvent $event
     * @priority HIGHEST
     */
    public function onEntityArmourChange(EntityArmorChangeEvent $event): void
    {
        $entity = $event->getEntity();

        if (!$entity instanceof Player || $event->isCancelled()) {
            return;
        }

        $this->getScheduler()->scheduleDelayedTask(new CheckArmourTask($this, $entity), 1);
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $this->removeEffects($event->getPlayer());
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event): void
    {
        $this->getScheduler()->scheduleDelayedTask(new CheckArmourTask($this, $event->getPlayer()), 1);
    }

    /**
     * @param Player $player
     * @return array|null
     */
    public function getArmourEffects(Player $player): ?array
    {
        $config = $this->getConfig();

        $inv = $player->getArmorInventory();

        $helmet = $inv->getHelmet()->getId();
        $chestplate = $inv->getChestplate()->getId();
        $leggings = $inv->getLeggings()->getId();
        $boots = $inv->getBoots()->getId();


        foreach ($config->get("effects", []) as $name => $array) {
            if (isset($array["needsPermissions"]) && $array["needsPermissions"] === true && !$player->hasPermission("armoureffects.effect." . $name)) {
                continue;
            }
            if (isset($array["helmet"]) && $array["helmet"] !== $helmet) {
                continue;
            }
            if (isset($array["chestplate"]) && $array["chestplate"] !== $chestplate) {
                continue;
            }
            if (isset($array["leggings"]) && $array["leggings"] !== $leggings) {
                continue;
            }
            if (isset($array["boots"]) && $array["boots"] !== $boots) {
                continue;
            }
            return ["name" => $name, "message" => $array["message"] ?? "", "effects" => $array["effects"] ?? []];
        }
        return null;
    }

    /**
     * @param Player $player
     */
    public function removeEffects(Player $player): void
    {
        if (!isset($this->players[strtolower($player->getName())])) {
            return;
        }

        foreach ($this->players[strtolower($player->getName())] as $effect) {
            $effectId = explode(":", $effect)[0];
            if (is_string($effect)) {
                $effectId = Effect::getEffectByName($effectId)->getId();
            }
            $player->removeEffect($effectId);
        }
    }

    /**
     * @param Player $player
     * @param array $array
     */
    public function applyEffects(Player $player, array $array): void
    {
        $name = $array["name"];
        $message = $array["message"];
        $effects = $array["effects"];

        foreach ($effects as $effect) {
            $effect = explode(":", $effect);
            if (is_string($effect[0])) {
                $effectId = Effect::getEffectByName($effect[0]);
            } else {
                $effectId = Effect::getEffect($effect[0]);
            }
            $player->addEffect(new EffectInstance($effectId, 20 * (int) $effect[1] ?? 20, ($effect[2] ?? 2) - 1));
        }

        $this->players[strtolower($player->getName())] = $effects;

        $player->sendMessage($this->format($message, $name));
    }

    /**
     * @param string $message
     * @param string $name
     * @return string
     */
    public function format(string $message, string $name): string
    {
        $message = str_replace("&0", TextFormat::BLACK, $message);
        $message = str_replace("&1", TextFormat::DARK_BLUE, $message);
        $message = str_replace("&2", TextFormat::DARK_GREEN, $message);
        $message = str_replace("&3", TextFormat::DARK_AQUA, $message);
        $message = str_replace("&4", TextFormat::DARK_RED, $message);
        $message = str_replace("&5", TextFormat::DARK_PURPLE, $message);
        $message = str_replace("&6", TextFormat::GOLD, $message);
        $message = str_replace("&7", TextFormat::GRAY, $message);
        $message = str_replace("&8", TextFormat::DARK_GRAY, $message);
        $message = str_replace("&9", TextFormat::BLUE, $message);
        $message = str_replace("&a", TextFormat::GREEN, $message);
        $message = str_replace("&b", TextFormat::AQUA, $message);
        $message = str_replace("&c", TextFormat::RED, $message);
        $message = str_replace("&d", TextFormat::LIGHT_PURPLE, $message);
        $message = str_replace("&e", TextFormat::YELLOW, $message);
        $message = str_replace("&f", TextFormat::WHITE, $message);
        $message = str_replace("&k", TextFormat::OBFUSCATED, $message);
        $message = str_replace("&l", TextFormat::BOLD, $message);
        $message = str_replace("&m", TextFormat::STRIKETHROUGH, $message);
        $message = str_replace("&n", TextFormat::UNDERLINE, $message);
        $message = str_replace("&o", TextFormat::ITALIC, $message);
        $message = str_replace("&r", TextFormat::RESET, $message);

        $message = str_replace("{name}", $name, $message);

        return $message;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
