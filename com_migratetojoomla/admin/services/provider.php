<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_migratetojoomla
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Component\MigrateToJoomla\Administrator\Extension\MigrateToJoomlaComponent;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The migratetojoomla service provider.
 *
 * @since  1.0
 */

return new class implements ServiceProviderInterface {

    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since  1.0
     */

    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\MigrateToJoomla'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\MigrateToJoomla'));
        $container->registerServiceProvider(new RouterFactory('\\Joomla\\Component\\MigrateToJoomla'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new MigrateToJoomlaComponent($container->get(ComponentDispatcherFactoryInterface::class));

                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
