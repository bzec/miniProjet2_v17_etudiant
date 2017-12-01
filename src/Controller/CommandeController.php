<?php
namespace App\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;   // pour utiliser request
use App\Model\PanierModel;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security;

use App\Model\ProduitModel;
use App\Model\ArchivageProduitModel;
use App\Model\UserModel;
use App\Model\CommandeModel;
class CommandeController implements ControllerProviderInterface
{
    private $panierModel;
    private $produitModel;
    private $userModel;
    private $commandeModel;
    private  $archivepanierModel;

    public function index(Application $app){
        return $this->showCommande($app);
    }

    public function ajouterCommande(Application $app){
        $this->userModel= new UserModel($app);
        $iduser=$this->userModel->recupererId($app);

        $this->commandeModel=new CommandeModel($app);
        $this->commandeModel->transaction($iduser);



       $this->panierModel=new PanierModel($app);
       $this->panierModel->deleteUserPanier($iduser);


        return $app->redirect($app["url_generator"]->generate("CommandeClient.show"));
    }

    public function showCommande(Application $app){
        $this->userModel= new UserModel($app);
        $iduser=$this->userModel->recupererId($app);

        $this->commandeModel = new CommandeModel($app);
        $donnees=$this->commandeModel->getCommande($iduser);
        return $app["twig"]->render('frontOff/showCommandeClient.html.twig',['data'=>$donnees]);

    }

    public function connect(Application $app) {  //http://silex.sensiolabs.org/doc/providers.html#controller-providers
        $controllers = $app['controllers_factory'];
       // $controllers->get('/', 'App\Controller\CommandeController::index')->bind('commande.index');
        $controllers->get('/add', 'App\Controller\CommandeController::ajouterCommande')->bind('CommandeClient.add');
        $controllers->get('/show', 'App\Controller\CommandeController::index')->bind('CommandeClient.show');


        return $controllers;
    }
}
