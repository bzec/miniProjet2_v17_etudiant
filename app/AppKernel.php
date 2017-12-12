<?php
require "config.php";

//On initialise le timeZone
ini_set('date.timezone', 'Europe/Paris');

//On ajoute l'autoloader (compatible winwin)
$loader = require_once join(DIRECTORY_SEPARATOR,[dirname(__DIR__), 'vendor', 'autoload.php']);

//dans l'autoloader nous ajoutons notre répertoire applicatif
$loader->addPsr4('App\\',join(DIRECTORY_SEPARATOR,[dirname(__DIR__), 'src']));

//Nous instancions un objet Silex\Application
$app = new Silex\Application();

// connexion à la base de données
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'dbhost' => hostname,
        'host' => hostname,
        'dbname' => database,
        'user' => username,
        'password' => password,
        'charset'   => 'utf8mb4',
    ),
));

//utilisation de twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => join(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'src', 'View'])
));

// utilisation des sessoins
$app->register(new Silex\Provider\SessionServiceProvider());

//en dev, nous voulons voir les erreurs
$app['debug'] = true;

// rajoute la méthode asset dans twig

$app->register(new Silex\Provider\AssetServiceProvider(), array(
    'assets.named_packages' => array(
        'css' => array(
            'version' => 'css2',
            'base_path' => __DIR__.'/../web/'
        ),
    ),
));

// par défaut les méthodes DELETE PUT ne sont pas prises en compte
use Symfony\Component\HttpFoundation\Request;
Request::enableHttpMethodParameterOverride();

//validator      => php composer.phar  require symfony/validator
$app->register(new Silex\Provider\ValidatorServiceProvider());

// Montage des controleurs sur le routeur
include('routing.php');

$app->before(function (\Symfony\Component\HttpFoundation\Request $request) use ($app) {
    $nomRoute=$request->get("_route");
    $routeAdmin=['CommandeVendeur.show','CommandeVendeur.details','CommandeVendeur.expedier','CommandeVendeur.annuler'
        ,'produit.showProduits','produit.addProduit','produit.validFormAddProduit','produit.deleteProduit'
        ,'produit.validFormDeleteProduit','produit.editProduit','produit.validFormEditProduit ','produit.Formreap' , 'produit.reap'];


    $routeClient=['CommandeClient.add','CommandeClient.show','CommandeClient.details','panier.index'
        ,'panier.add','panier.addFromAddPanier','panier.show','panier.deleteProduit'
        ,'panier.validFormDeletePanier','produitClient.show','produit.validTriage','produit.detailsProduit'];

    $routeVisiteur=['produitVisiteur.showProduits','panier.validTriage'];

    if (($app['session']->get('roles') != 'ROLE_ADMIN' && $app['session']->get('roles') != 'ROLE_VENDEUR' ) && in_array($nomRoute, $routeAdmin) ) {
        return $app->redirect($app["url_generator"]->generate("index.errorDroit"));
    }

    if ($app['session']->get('roles') != 'ROLE_CLIENT' &&   in_array($nomRoute, $routeClient)) {
        return $app->redirect($app["url_generator"]->generate("index.errorDroit"));
    }

    if (($app['session']->get('roles') == 'ROLE_ADMIN' || $app['session']->get('roles') == 'ROLE_CLIENT' || $app['session']->get('roles') == 'ROLE_VENDEUR') && $nomRoute=="user.login") {
        return $app->redirect($app["url_generator"]->generate("index.errorDroit"));
    }

});
use Silex\Provider\CsrfServiceProvider;
$app->register(new CsrfServiceProvider());

use Silex\Provider\FormServiceProvider;
use Symfony\Component\Security\Csrf\CsrfToken;

$app->register(new FormServiceProvider());


$app->before(function (\Symfony\Component\HttpFoundation\Request $request) use ($app) {

    if ($request->getMethod()=='POST' || $request->getMethod()=='PUT' || $request->getMethod()=='DELETE') {
        if (isset($_POST['_csrf_token'])) {

            $token = $_POST['_csrf_token'];
            $csrf_token = new CsrfToken('token', $token);
            $csrf_token_ok = $app['csrf.token_manager']->isTokenValid($csrf_token);
            if (!$csrf_token_ok) {
                $erreurs["csrf"] = "Erreur : token : " . $token;
                return $app->redirect($app["url_generator"]->generate("index.errorCsrf"));
            }
        }
    }


});

//On lance l'application
$app->run();