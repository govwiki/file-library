<?php

namespace App\Kernel;

use App\Controller\FileController;
use App\Controller\SecurityController;
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

        $app->delete('/files/{slug}', FileController::class . ':remove')->setName('files-remove');
        $app->post('/files/{slug}/upload', FileController::class . ':upload')->setName('files-upload');
        $app->put('/files/{slug}', FileController::class . ':update')->setName('files-update');
        $app->get('/files/{slug}', FileController::class . ':files')->setName('files');
        $app->get('/files', FileController::class . ':files')->setName('files-root');
        $app->get('/{slug}', FileController::class . ':index')->setName('index');
        $app->get('/', FileController::class . ':index')->setName('index');

        return $app;
    }
}
