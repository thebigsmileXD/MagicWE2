<?php

declare(strict_types=1);

namespace xenialdan\MagicWE2\helper;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\UUID;
use xenialdan\MagicWE2\exception\SessionException;
use xenialdan\MagicWE2\session\PluginSession;
use xenialdan\MagicWE2\session\Session;
use xenialdan\MagicWE2\session\UserSession;

class SessionHelper
{
    /** @var \Ds\Map */
    private static $userSessions = [];
    /** @var \Ds\Map */
    private static $pluginSessions = [];

    public static function addSession(Session $session): void
    {
        if ($session instanceof UserSession) self::$userSessions->put($session->getUUID(), $session);
        else if ($session instanceof PluginSession) self::$pluginSessions->put($session->getUUID(), $session);
    }

    /**
     * Destroys a session and removes it from cache. Saves to file if $save is true
     * @param Session $session
     * @param bool $save
     */
    public static function destroySession(Session $session, bool $save = true)
    {
        if ($session instanceof UserSession) self::$userSessions->remove($session->getUUID());
        else if ($session instanceof PluginSession) self::$pluginSessions->remove($session->getUUID());
        //TODO save
        /** @noinspection PhpStatementHasEmptyBodyInspection */
        if ($save) {

        }
        unset($session);
    }

    /**
     * Creates an UserSession used to execute MagicWE2's functions
     * @param Player $player
     * @param bool $add If true, the session will be cached in SessionHelper
     * @return UserSession
     * @throws \InvalidStateException
     * @throws SessionException
     */
    public static function createUserSession(Player $player, bool $add = true): UserSession
    {
        if (!$player->hasPermission("we.session")) throw new SessionException(TF::RED . "You do not have the permission \"magicwe.session\"");
        $session = new UserSession($player);
        if ($add) self::addSession($session);
        return $session;
    }

    /**
     * Creates a PluginSession used to call API functions via a plugin
     * @param Plugin $plugin
     * @param bool $add If true, the session will be cached in SessionHelper
     * @return PluginSession
     */
    public static function createPluginSession(Plugin $plugin, bool $add = true): PluginSession
    {
        $session = new PluginSession($plugin);
        if ($add) self::addSession($session);
        return $session;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public static function hasSession(Player $player): bool
    {
        try {
            return self::getUserSession($player) instanceof UserSession;
        } catch (SessionException $exception) {
            return false;
        }
    }

    /**
     * @param Player $player
     * @return null|UserSession
     * @throws SessionException
     */
    public static function getUserSession(Player $player): ?UserSession
    {
        $filtered = self::$userSessions->filter(function (Session $session) use ($player) {
            return $session instanceof UserSession && $session->getPlayer() === $player;
        });
        if (count($filtered) > 1) throw new SessionException("Multiple sessions found for player {$player->getName()}. This should never happen!");
        return current($filtered) ?? null;
    }

    /**
     * @param UUID $uuid
     * @return null|Session
     */
    public static function getSessionByUUID(UUID $uuid): ?Session
    {
        return self::$userSessions->get($uuid->toString(), null) ?? self::$pluginSessions->get($uuid->toString(), null) ?? null;
    }

    /**
     * @return \Ds\Map
     */
    public static function getUserSessions(): \Ds\Map
    {
        return self::$userSessions;
    }

}