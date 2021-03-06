<?php
namespace App\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;   // modif version 2.0


class IndexController implements ControllerProviderInterface
{
    public function index(Application $app)
    {
        if ($app['session']->get('roles') == 'ROLE_CLIENT')
             return $app["twig"]->render("frontOff/frontOFFICE.html.twig"); 
        // remplacer par une redirection :  return $app->redirect($app["url_generator"]->generate("Panier.index"));
        if ($app['session']->get('roles') == 'ROLE_ADMIN')
            return $app["twig"]->render("backOff/backOFFICE.html.twig");
     
        if($app['session']->get('roles') == 'ROLE_VENDEUR'){
            return $app["twig"]->render("backOff/backOFFICE.html.twig");
        }
        
        return $app["twig"]->render("accueil.html.twig");
    }
    public function errorDroit(Application $app){
        return $app['twig']->render('errorDroit.html.twig');
    }
    public function errorCsrf(Application $app){
        return $app['twig']->render('v_error_csrf.html.twig');
    }


    public function connect(Application $app)
    {
        $index = $app['controllers_factory'];
        $index->match("/", 'App\Controller\IndexController::index')->bind('accueil');
        $index->match("/errorDroit", 'App\Controller\IndexController::errorDroit')->bind("index.errorDroit");
        $index->match("/errorCsrf", 'App\Controller\IndexController::errorCsrf')->bind("index.errorCsrf");
        return $index;
    }


}
