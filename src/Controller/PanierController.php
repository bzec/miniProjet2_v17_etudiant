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
        return $app["twig"]->render('frontOff/Panier/showPanierClient.html.twig',['data'=>$panier]);
    }

    public function addPanierClient(Application $app,$idProduit){
        
    $this->produitModel=new ProduitModel($app);
    $data= $this->produitModel->getProduit($idProduit);

    return $app["twig"]->render('frontOff/Panier/addPanierClient.html.twig',['data'=>$data]);
    }


    public function addFromPanierClient(Application $app){


        if(isset($_POST['quantite']) && isset($_POST['idProduit'])){
        $donnees['quantite']=htmlspecialchars($_POST['quantite']);
        $data['id']=htmlspecialchars($_POST['idProduit']);
        $stock=htmlentities($_POST['stock']);
       
        
        if(! is_numeric($donnees['quantite']))$erreurs['quantite']='veuillez saisir une valeur';
        if ($donnees['quantite']>$stock)$erreurs['stock']='stock insuffisent';
        $this->produitModel=new ProduitModel($app);
        $this->panierModel = new PanierModel($app);
        $this->userModel=new UserModel($app);
        $this->archivepanierModel = new ArchivePanierModel($app);
        $id=$this->userModel->recupererId($app);
        $data=$this->produitModel->getProduit($data['id']);

        $donnees['user_id']=$id;
        $donnees['produit_id']=$data['id'];
        $donnees['dateAjoutPanier']=(new \DateTime())->format('Y-m-d');
        $newstock=$stock-$donnees['quantite'];
        $panierProd=$this->panierModel->recupererProduitPanier($donnees);

        if(empty($erreurs)){
    
            if (!empty($panierProd)){
                $donnees['id']=$panierProd['id'];


                $donnees['quantite']=$panierProd['quantite']+$donnees['quantite'];
                $this->produitModel->updateStockProduit($newstock,$donnees['produit_id']);

                $donnees['prix']=$data['prix'] * $donnees['quantite'];
                $this->panierModel->updatePanier($donnees);
                $this->archivepanierModel->updateArchivePanier($donnees);

                return $app->redirect($app["url_generator"]->generate("produitClient.show"));
                
            }else{
              
                $donnees['prix']=$data['prix'] * $donnees['quantite'];

                $this->panierModel->ajouterDansPanier($donnees);
                $this->produitModel->updateStockProduit($newstock,$donnees['produit_id']);
                $dataArchive =$this->panierModel->readUnPanierProduit($donnees['user_id'],$donnees['produit_id']);
                $this->archivepanierModel->ajouterDansArchivagePanier($dataArchive);
                return $app->redirect($app["url_generator"]->generate("produitClient.show"));
            }
        }else { 
         
            return $app["twig"]->render('frontOff/Panier/addPanierClient.html.twig',['donnees'=>$donnees,'data'=>$data,'erreurs'=>$erreurs]);
        }
    }
        else{
            return $app->abort(404, 'error Pb id form AddPanier');
        }
        

    }

    public function deleteProduitDansPanier(Application $app,$id) {

        $this->panierModel = new PanierModel($app);
        $panier = $this->panierModel->readUnPanierSuppr($id);


        return $app["twig"]->render('frontOff/Panier/deletePanierClient.html.twig',['panier'=>$panier]);
    }

    public function validFormDeletePanier(Application $app, Request $req) {
        $id=$app->escape($req->get('id'));
        if (is_numeric($id)) {
            $this->produitModel=new ProduitModel($app);
            $this->panierModel = new PanierModel($app);

            $panier=$this->panierModel->readUnPanierSuppr($id);


            $produit=$this->produitModel->getProduit($panier['produit_id']);

            $this->produitModel->updateStockProduit($panier['quantite'] + $produit['stock'],$panier['produit_id']);
            $this->panierModel->deletePanier($id);

            $this->archivepanierModel = new ArchivePanierModel($app);
            $this->archivepanierModel->deleteArchivePanier($id);

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
