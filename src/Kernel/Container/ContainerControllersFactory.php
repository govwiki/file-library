<?php

namespace App\Kernel\Container;

use App\Controller\DocumentApiController;
use App\Controller\DocumentController;
use App\Controller\ErrorHandler;
use App\Controller\SecurityController;
use App\Entity\DocumentFactory;
use App\Repository\DocumentRepositoryInterface;
use App\Service\Authenticator\AuthenticatorInterface;
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

        /**
         * Document controller.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return DocumentController
         */
        $container[DocumentController::class] = function (ContainerInterface $container): DocumentController {
            /** @var Twig $view */
            $view = $container->get('view');
            /** @var DocumentRepositoryInterface $repository */
            $repository = $container->get(DocumentRepositoryInterface::class);

            return new DocumentController($view, $repository);
        };

        /**
         * Document API controller.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return DocumentApiController
         */
        $container[DocumentApiController::class] = function (ContainerInterface $container): DocumentApiController {
            /** @var DocumentRepositoryInterface $repository */
            $repository = $container->get(DocumentRepositoryInterface::class);
            /** @var DocumentFactory $factory */
            $factory = $container->get(DocumentFactory::class);

            return new DocumentApiController($repository, $factory);
        };

        /**
         * SecurityController controller.
         *
         * @param ContainerInterface $container A ContainerInterface instance.
         *
         * @return SecurityController
         */
        $container[SecurityController::class] = function (ContainerInterface $container): SecurityController {
            /** @var AuthenticatorInterface $authenticator */
            $authenticator = $container->get(AuthenticatorInterface::class);
            /** @var Twig $view */
            $view = $container->get('view');
            /** @var RouterInterface $router */
            $router = $container->get('router');
            /** @var Helper $session */
            $session = $container->get('session');
            /** @var string $domain */
            $domain = $container->get('settings')['domain'];

            return new SecurityController($authenticator, $view, $router, $session, $domain);
        };
    }
}
