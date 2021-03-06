<?php

declare(strict_types=1);

namespace xenialdan\MagicWE2\commands\region;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\ArgumentOrderException;
use Exception;
use InvalidArgumentException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use xenialdan\MagicWE2\API;
use xenialdan\MagicWE2\exception\SelectionException;
use xenialdan\MagicWE2\exception\SessionException;
use xenialdan\MagicWE2\helper\SessionHelper;
use xenialdan\MagicWE2\Loader;

class SetCommand extends BaseCommand
{

	/**
	 * This is where all the arguments, permissions, sub-commands, etc would be registered
	 * @throws ArgumentOrderException
	 * @throws InvalidArgumentException
	 */
	protected function prepare(): void
	{
		$this->registerArgument(0, new RawStringArgument("blocks", false));
		$this->registerArgument(1, new TextArgument("flags", true));
		$this->setPermission("we.command.region.set");
	}

	/**
	 * @param CommandSender $sender
	 * @param string $aliasUsed
	 * @param mixed[] $args
	 */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $lang = Loader::getInstance()->getLanguage();
        if ($sender instanceof Player && SessionHelper::hasSession($sender)) {
            try {
                $lang = SessionHelper::getUserSession($sender)->getLanguage();
            } catch (SessionException $e) {
            }
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage(TF::RED . $lang->translateString('error.runingame'));
            return;
        }
        /** @var Player $sender */
        try {
			$messages = [];
			$error = false;
			$replaceBlocks = API::blockParser((string)$args["blocks"], $messages, $error);//TODO change to Palette
			foreach ($messages as $message) {
				$sender->sendMessage($message);
			}
			if (!$error) {
				$session = SessionHelper::getUserSession($sender);
				if (is_null($session)) {
					throw new SessionException($lang->translateString('error.nosession', [Loader::getInstance()->getName()]));
				}
				$selection = $session->getLatestSelection();
				if (is_null($selection)) {
					throw new SelectionException($lang->translateString('error.noselection'));
				}
				if (!$selection->isValid()) {
					throw new SelectionException($lang->translateString('error.selectioninvalid'));
				}
				if ($selection->getWorld() !== $sender->getWorld()) {
					$sender->sendMessage(Loader::PREFIX . TF::GOLD . $lang->translateString('warning.differentworld'));
				}
				$hasFlags = isset($args["flags"]);
				API::fillAsync($selection, $session, $replaceBlocks, $hasFlags ? API::flagParser(explode(" ", (string)$args["flags"])) : API::FLAG_BASE);
			} else {
				throw new InvalidArgumentException("Could not fill with the selected blocks");
			}
		} catch (Exception $error) {
			$sender->sendMessage(Loader::PREFIX . TF::RED . $lang->translateString('error.command-error'));
			$sender->sendMessage(Loader::PREFIX . TF::RED . $error->getMessage());
			$sender->sendMessage($this->getUsage());
		}
	}
}
