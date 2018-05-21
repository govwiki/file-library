<?php

namespace App\Kernel\Container;

use App\Controller\ErrorHandler;
use App\Controller\FileController;
use App\Controller\ProfileController;
use App\Controller\SecurityController;
use App\Repository\FileRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\Authenticator\AuthenticatorInterface;
use App\Service\DocumentMover\DocumentMoverService;
use App\Storage\Storage;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouterInterface;
use Slim\Views\Twig;
use SlimSession\Helper;

/**
 * Class ContainerControllersFactory
 *
 * @package App\Kernel\Container
 */
class ContainerControllersFactory
{

    /**
     * @param ContainerInterface $container Application container.
     *
     * @return void
     */
    public static function register(ContainerInterface $container)
    {
        /** @psalm-suppress MixedArrayAccess */
        if ($container['settings']['debug'] === false) {
            $container['errorHandler'] = function (ContainerInterface $container): ErrorHandler {
                /** @var Twig $renderer */
                $renderer = $container->get('view');

                return new ErrorHandler($renderer, 'error');
            };
            $container['notFoundHandler'] = function (ContainerInterface $container): ErrorHandler {
                /** @var Twig $renderer */
                $renderer = $container->get('view');

                return new ErrorHandler($renderer, 'error');
            };
        }

        $container[DocumentMoverService::class] = function (ContainerInterface $container): DocumentMoverService {
            /** @var Storage $storage */
            $storage = $container->get(Storage::class);

            return new DocumentMoverService($storage);
        };

        $container[ProfileController::class] = function (ContainerInterface $container): ProfileController {
            /** @var Twig $view */
            $view = $container->get('view');
            /** @var RouterInterface $router */
            $router = $container->get('router');
            /** @var UserRepositoryInterface $repository */
            $repository = $container->get(UserRepositoryInterface::class);

            return new ProfileController($view, $router, $repository);
        };

        /**
         * File controller.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return FileController
         */
        $container[FileController::class] = function (ContainerInterface $container): FileController {
            /** @var Twig $view */
            $view = $container->get('view');
            /** @var FileRepositoryInterface $repository */
            $repository = $container->get(FileRepositoryInterface::class);
            /** @var Storage $storage */
            $storage = $container->get(Storage::class);
            /** @var DocumentMoverService $mover */
            $mover = $container->get(DocumentMoverService::class);

            return new FileController($view, $repository, $storage, $mover);
        };

        /**
         * SecurityController controller.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return SecurityController
         */
        $container[SecurityController::class] = function (ContainerInterface $container): SecurityController {
            /** @var array{domain: string} $settings */
            $settings = $container->get('settings');

            if (! isset($settings['domain'])) {
                throw new \InvalidArgumentException('Required settings options "domain" is not set');
            }

            /** @var AuthenticatorInterface $authenticator */
            $authenticator = $container->get(AuthenticatorInterface::class);
            /** @var Twig $view */
            $view = $container->get('view');
            /** @var RouterInterface $router */
            $router = $container->get('router');
            /** @var Helper $session */
            $session = $container->get('session');
            /** @var string $domain */
            $domain = $settings['domain'];

            return new SecurityController($authenticator, $view, $router, $session, $domain);
        };
    }
}
