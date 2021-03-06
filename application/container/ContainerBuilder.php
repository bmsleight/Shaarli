<?php

declare(strict_types=1);

namespace Shaarli\Container;

use Shaarli\Bookmark\BookmarkFileService;
use Shaarli\Bookmark\BookmarkServiceInterface;
use Shaarli\Config\ConfigManager;
use Shaarli\History;
use Shaarli\Plugin\PluginManager;
use Shaarli\Render\PageBuilder;
use Shaarli\Security\LoginManager;
use Shaarli\Security\SessionManager;

/**
 * Class ContainerBuilder
 *
 * Helper used to build a Slim container instance with Shaarli's object dependencies.
 * Note that most injected objects MUST be added as closures, to let the container instantiate
 * only the objects it requires during the execution.
 *
 * @package Container
 */
class ContainerBuilder
{
    /** @var ConfigManager */
    protected $conf;

    /** @var SessionManager */
    protected $session;

    /** @var LoginManager */
    protected $login;

    public function __construct(ConfigManager $conf, SessionManager $session, LoginManager $login)
    {
        $this->conf = $conf;
        $this->session = $session;
        $this->login = $login;
    }

    public function build(): ShaarliContainer
    {
        $container = new ShaarliContainer();
        $container['conf'] = $this->conf;
        $container['sessionManager'] = $this->session;
        $container['loginManager'] = $this->login;
        $container['plugins'] = function (ShaarliContainer $container): PluginManager {
            return new PluginManager($container->conf);
        };

        $container['history'] = function (ShaarliContainer $container): History {
            return new History($container->conf->get('resource.history'));
        };

        $container['bookmarkService'] = function (ShaarliContainer $container): BookmarkServiceInterface {
            return new BookmarkFileService(
                $container->conf,
                $container->history,
                $container->loginManager->isLoggedIn()
            );
        };

        $container['pageBuilder'] = function (ShaarliContainer $container): PageBuilder {
            return new PageBuilder(
                $container->conf,
                $container->sessionManager->getSession(),
                $container->bookmarkService,
                $container->sessionManager->generateToken(),
                $container->loginManager->isLoggedIn()
            );
        };

        $container['pluginManager'] = function (ShaarliContainer $container): PluginManager {
            return new PluginManager($container->conf);
        };

        return $container;
    }
}
