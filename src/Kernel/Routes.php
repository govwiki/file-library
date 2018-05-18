<?php

namespace App\Kernel;

use App\Controller\FileController;
use App\Controller\ProfileController;
use App\Controller\SecurityController;
use App\Middleware\AuthorizationCheckMiddleware;
use Slim\App;

/**
 * Class Routes
 *
 * @package App\Kernel
 */
class Routes
{

    /**
     * @param App $app A slim application instance.
     *
     * @return App
     */
    public static function registerRoutes(App $app): App
    {
        //
        // Security routes.
        //
        $app->map([ 'GET', 'POST' ], '/login', SecurityController::class .':login')->setName('login');
        $app->get('/logout', SecurityController::class .':logout')->setName('logout');

        $app->group('', function () use ($app) {
            $app->delete('/files/{slug}', FileController::class . ':remove')->setName('file-remove');
            $app->post('/files/{slug}/upload', FileController::class . ':upload')->setName('file-upload');
            $app->put('/files/{slug}/rename', FileController::class . ':rename')->setName('file-rename');
            $app->put('/files/{slug}/move', FileController::class . ':move')->setName('file-move');
        })->add(new AuthorizationCheckMiddleware());

        $app->map([ 'GET', 'POST' ], '/profile', ProfileController::class . ':index')->setName('profile');

        $app->get('/files/{slug}', FileController::class . ':files')->setName('files');
        $app->get('/files', FileController::class . ':files')->setName('files-root');
        $app->get('/{slug}', FileController::class . ':index')->setName('index');
        $app->get('/', FileController::class . ':index')->setName('index');

        return $app;
    }
}
