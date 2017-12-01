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
use App\Model\ArchivePanierModel;
use App\Model\ProduitModel;
use App\Model\UserModel;

class PanierController implements ControllerProviderInterface
{
    private $panierModel;
    private $produitModel;
    private $userModel;
    private $archivepanierModel;

    public function index(Application $app) {

        return $this->showPanierClient($app);
    }


    public function showPanierClient(Application $app) {
        $this->panierModel = new PanierModel($app);
        $this->userModel=new UserModel($app);
        $id=$this->userModel->recupererId($app);
        $panier = $this->panierModel->readUnPanier($id);
        return $app["twig"]->render('frontOff/showPanierClient.html.twig',['data'=>$panier]);
    }

    public function addPanierClient(Application $app,$idProduit){
        
    $this->produitModel=new ProduitModel($app);
    $data= $this->produitModel->getProduit($idProduit);

    return $app["twig"]->render('frontOff/addPanierClient.html.twig',['data'=>$data]);
    }


    public function addFromPanierClient(Application $app){


        if(isset($_POST['quantite']) && isset($_POST['idProduit'])){
        $donnees['quantite']=htmlspecialchars($_POST['quantite']);
        $data['id']=htmlspecialchars($_POST['idProduit']);
       
        
        if(! is_numeric($donnees['quantite']))$erreurs['quantite']='veuillez saisir une valeur';
        $this->produitModel=new ProduitModel($app);
        $this->panierModel = new PanierModel($app);
        $this->userModel=new UserModel($app);
        $this->archivepanierModel = new ArchivePanierModel($app);
        $id=$this->userModel->recupererId($app);
        $data=$this->produitModel->getProduit($data['id']);

        $donnees['user_id']=$id;
        $donnees['produit_id']=$data['id'];
        $donnees['dateAjoutPanier']=(new \DateTime())->format('Y-m-d');

        $panierProd=$this->panierModel->recupererProduitPanier($donnees);
        if(empty($erreurs)){
    
            if (!empty($panierProd)){
                $donnees['id']=$panierProd['id'];
               // echo $donnees['quantite'];
                $donnees['quantite']=$panierProd['quantite']+$donnees['quantite'];
                $donnees['prix']=$data['prix'] * $donnees['quantite'];
                $this->panierModel->updatePanier($donnees);
                return $app->redirect($app["url_generator"]->generate("produitClient.show"));
                
            }else{
              
                $donnees['prix']=$data['prix'] * $donnees['quantite'];

                $this->panierModel->ajouterDansPanier($donnees);
                $dataArchive =$this->panierModel->readUnPanierProduit($donnees['user_id'],$donnees['produit_id']);
                $this->archivepanierModel->ajouterDansArchivagePanier($dataArchive);
                return $app->redirect($app["url_generator"]->generate("produitClient.show"));
            }
        }else { 
         
            return $app["twig"]->render('frontOff/addPanierClient.html.twig',['donnees'=>$donnees,'data'=>$data,'erreurs'=>$erreurs]);
        }
    }
        else{
            return $app->abort(404, 'error Pb id form AddPanier');
        }
        

    }
    //faire la route pour show pour rester sur chaud et non retourner sur le show pnier juste un coper coller en modifiant la route je le ferai tkt
    public function deleteProduitDansPanier(Application $app,$id) {

        $this->panierModel = new PanierModel($app);
        $panier = $this->panierModel->readUnPanierSuppr($id);

        return $app["twig"]->render('frontOff/deletePanierClient.html.twig',['panier'=>$panier]);
    }

    public function validFormDeletePanier(Application $app, Request $req) {
        $id=$app->escape($req->get('id'));
        if (is_numeric($id)) {
            $this->panierModel = new PanierModel($app);
            $this->panierModel->deletePanier($id);
            return $app->redirect($app["url_generator"]->generate("produitClient.show"));
        }
        else
            return $app->abort(404, 'error Pb id form Delete');
    }

    public function connect(Application $app) {  //http://silex.sensiolabs.org/doc/providers.html#controller-providers
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'App\Controller\PanierController::index')->bind('panier.index');
        $controllers->get('/addPanier/{idProduit}','App\Controller\PanierController::addPanierClient')->bind('panier.add');
        $controllers->post('/addPanier/ ','App\Controller\PanierController::addFromPanierClient')->bind('panier.addFromAddPanier');
       
        $controllers->get('/showPanier', 'App\Controller\PanierController::showPanierClient')->bind('panier.show');
       
        $controllers->get('/delete/{id}', 'App\Controller\PanierController::deleteProduitDansPanier')->bind('panier.deleteProduit')->assert('id', '\d+');
        $controllers->delete('/delete', 'App\Controller\PanierController::validFormDeletePanier')->bind('panier.validFormDeletePanier');
        
        return $controllers;
    }
}
