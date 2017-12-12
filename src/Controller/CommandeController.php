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
use App\Model\ArchivePanierModel;
use App\Model\UserModel;
use App\Model\CommandeModel;
class CommandeController implements ControllerProviderInterface
{
    private $panierModel;
    private $produitModel;
    private $userModel;
    private $commandeModel;
    private $archivepanierModel;

    public function index(Application $app){
        return $this->showCommandeClient($app);
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

    public function showCommandeClient(Application $app){
        $this->userModel= new UserModel($app);
        $iduser=$this->userModel->recupererId($app);

        $this->commandeModel = new CommandeModel($app);
        $donnees=$this->commandeModel->getCommandeClient($iduser);

        $total=0;
        foreach ($donnees as $value){
            $total+=$value['prix'];
        }
       return $app["twig"]->render('frontOff/Commande/showCommandeClient.html.twig',['data'=>$donnees , 'prixtotal'=>$total]);
    }


    public function detailsCommandeClient(Application $app,$id){

        $this->userModel= new UserModel($app);
        $iduser=$this->userModel->recupererId($app);

        $this->commandeModel = new CommandeModel($app);
        $this->archivepanierModel= new ArchivePanierModel($app);
        $donnees=$this->archivepanierModel->readPanierCommande($iduser,$id);
        $prixtototal=0;
        foreach($donnees as $p){
            $prixtototal+=$p['prix'];
        }
        return $app["twig"]->render('frontOff/Commande/detailsCommandeClient.html.twig',['data'=>$donnees , 'prixtotal'=>$prixtototal]);
    }

    public function showCommandeVendeur (Application $app){
        $this->userModel= new UserModel($app);
        $iduser=$this->userModel->recupererId($app);

        $this->commandeModel = new CommandeModel($app);
        $donnees=$this->commandeModel->getAllCommande();

        return $app["twig"]->render('backOff/Commande/showCommandeVendeur.html.twig',['data'=>$donnees]);

    }

    public function detailsCommandeVendeur(Application $app,$id){

        $this->archivepanierModel= new ArchivePanierModel($app);
        $donnees=$this->archivepanierModel->readPanierCommandeVendeur($id);
        $prixtototal=0;
        foreach($donnees as $p){
            $user=$p['username'];
            $prixtototal+=$p['prix'];
        }
        //echo $user;
        return $app["twig"]->render('backOff/Commande/detailsCommandeVendeur.html.twig',['data'=>$donnees, 'prixtotal'=>$prixtototal,'username'=>$user]);
    }

    public function updateEtat(Application $app,$idcom){

        $this->commandeModel = new CommandeModel($app);
        $this->commandeModel->updateEtatCommande($idcom);
        return $app->redirect($app["url_generator"]->generate("CommandeVendeur.show"));
    }

    public function deleteCommande(Application $app,$idcom){

        $this->commandeModel = new CommandeModel($app);
        $this->commandeModel->deleteCommande($idcom);
        return $app->redirect($app["url_generator"]->generate("CommandeVendeur.show"));
    }

    public function connect(Application $app) {  //http://silex.sensiolabs.org/doc/providers.html#controller-providers
        $controllers = $app['controllers_factory'];
        $controllers->get('/add', 'App\Controller\CommandeController::ajouterCommande')->bind('CommandeClient.add');

        $controllers->get('/show', 'App\Controller\CommandeController::showCommandeClient')->bind('CommandeClient.show');
        $controllers->get('/show/details/{id}', 'App\Controller\CommandeController::detailsCommandeClient')->bind('CommandeClient.details');

        $controllers->get('/show/', 'App\Controller\CommandeController::showCommandeVendeur')->bind('CommandeVendeur.show');
        $controllers->get('/show/detailsV/{id}', 'App\Controller\CommandeController::detailsCommandeVendeur')->bind('CommandeVendeur.details');
        $controllers->get('/show/expedier/{idcom}', 'App\Controller\CommandeController::updateEtat')->bind('CommandeVendeur.expedier');
        $controllers->get('/show/annuler/{idcom}', 'App\Controller\CommandeController::deleteCommande')->bind('CommandeVendeur.annuler');
        return $controllers;
    }
}
