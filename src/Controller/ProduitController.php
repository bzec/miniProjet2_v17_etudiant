<?php
namespace App\Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;   // pour utiliser request

use App\Model\ProduitModel;
use App\Model\TypeProduitModel;
use App\Model\PanierModel;
use App\Model\UserModel;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security;


class ProduitController implements ControllerProviderInterface
{
    private $produitModel;
    private $typeProduitModel;
    private $userModel;
    private $panierModel;

    public function index(Application $app)
    {
        return $this->showProduits($app);
    }

    public function showProduits(Application $app)
    {
        $this->produitModel = new ProduitModel($app);
        $produits = $this->produitModel->getAllProduits();
        return $app["twig"]->render('backOff/Produit/showProduits.html.twig', ['data' => $produits]);
    }

    public function showProduitsClient(Application $app)
    {
        $this->produitModel = new ProduitModel($app);
        $produits = $this->produitModel->getAllProduits();
        $this->typeProduitModel = new TypeProduitModel($app);
        $typeProduits = $this->typeProduitModel->getAllTypeProduits();
        $this->panierModel = new PanierModel($app);
        $this->userModel = new UserModel($app);
        $id = $this->userModel->recupererId($app);
        $panier = $this->panierModel->readUnPanier($id);
        return $app["twig"]->render('frontOff/Produit/showProduitsClient.html.twig', ['data' => $produits, 'panier' => $panier, 'typeProduits' => $typeProduits]);
    }


    public function addProduit(Application $app)
    {
        $this->typeProduitModel = new TypeProduitModel($app);
        $typeProduits = $this->typeProduitModel->getAllTypeProduits();
        return $app["twig"]->render('backOff/Produit/addProduit.html.twig', ['typeProduits' => $typeProduits]);
    }

    public function validFormAddProduit(Application $app, Request $req)
    {

        if (isset($_POST['nom']) && isset($_POST['typeProduit_id']) and isset($_POST['prix'])) {
            $donnees = [
                'nom' => htmlspecialchars($_POST['nom']),                    // echapper les entrées
                'typeProduit_id' => htmlspecialchars($req->get('typeProduit_id')),
                'prix' => htmlspecialchars($req->get('prix')),
                'photo' => $app->escape($req->get('photo'))
            ];
            if ((! preg_match("/^[A-Za-z ]{2,}/",$donnees['nom']))) $erreurs['nom']='nom composé de 2 lettres minimum';
            if(! is_numeric($donnees['typeProduit_id']))$erreurs['typeProduit_id']='veuillez saisir une valeur';
            if(! is_numeric($donnees['prix']))$erreurs['prix']='saisir une valeur numérique';
           // if (! preg_match("/[A-Za-z0-9]{2,}.(jpeg|jpg|png)/",$donnees['photo'])) $erreurs['photo']='nom de fichier incorrect (extension jpeg , jpg ou png)';

            $_FILES['photo']['name'];     //Le nom original du fichier, comme sur le disque du visiteur (exemple : mon_photo.png).

            $_FILES['photo']['type'];     //Le type du fichier. Par exemple, cela peut être « image/png ».

            $_FILES['photo']['size'];     //La taille du fichier en octets.

            $_FILES['photo']['tmp_name']; //L'adresse vers le fichier uploadé dans le répertoire temporaire.

            $_FILES['photo']['error'];    //Le code d'erreur, qui permet de savoir si le fichier a bien été uploadé.

            if ($_FILES['photo']['error'] > 0) $erreurs['image'] = "Erreur lors du transfert";
            if ($_FILES['photo']['size'] > $_POST['maxsize']) $erreurs['image'] = "Le fichier est trop gros";
            $extensions_valides = array( 'jpg' , 'jpeg' , 'gif' , 'png' );
            $extension_upload = strtolower(  substr(  strrchr($_FILES['photo']['name'], '.')  ,1)  );
            if ( !in_array($extension_upload,$extensions_valides) ) $erreurs['image'] = "Extension incorrect : il faut soit jpg, jpeg, gif, png";

            if(! empty($erreurs))
            {
                $this->typeProduitModel = new TypeProduitModel($app);
                $typeProduits = $this->typeProduitModel->getAllTypeProduits();
                return $app["twig"]->render('backOff/Produit/addProduit.html.twig',['donnees'=>$donnees,'erreurs'=>$erreurs,'typeProduits'=>$typeProduits]);
            }
            else
            {
                $this->produitModel = new ProduitModel($app);
                $nom = "../public/images/{$donnees['nom']}.{$extension_upload}";
                $donnees['photo'] = "{$donnees['nom']}.{$extension_upload}";
                $this->produitModel->insertProduit($donnees);
                move_uploaded_file($_FILES['photo']['tmp_name'],$nom);
                return $app->redirect($app["url_generator"]->generate("produit.index"));
            }

        }
        else
            return $app->abort(404, 'error Pb data form Add');
    }

    public function deleteProduit(Application $app, $id) {
        $this->typeProduitModel = new TypeProduitModel($app);
        $typeProduits = $this->typeProduitModel->getAllTypeProduits();
        $this->produitModel = new ProduitModel($app);
        $donnees = $this->produitModel->getProduit($id);
        return $app["twig"]->render('backOff/Produit/deleteProduit.html.twig',['typeProduits'=>$typeProduits,'donnees'=>$donnees]);
    }

    public function validFormDeleteProduit(Application $app, Request $req) {
        $id=$app->escape($req->get('id'));
        if (is_numeric($id)) {
            $this->produitModel = new ProduitModel($app);
            $this->produitModel->deleteProduit($id);
            return $app->redirect($app["url_generator"]->generate("produit.index"));
        }
        else
            return $app->abort(404, 'error Pb id form Delete');
    }


    public function editProduit(Application $app, $id) {
        $this->typeProduitModel = new TypeProduitModel($app);
        $typeProduits = $this->typeProduitModel->getAllTypeProduits();
        $this->produitModel = new ProduitModel($app);
        $donnees = $this->produitModel->getProduit($id);
        return $app["twig"]->render('backOff/Produit/editProduit.html.twig',['typeProduits'=>$typeProduits,'donnees'=>$donnees]);
    }

    public function validFormEditProduit(Application $app, Request $req) {
        if (isset($_POST['nom']) && isset($_POST['typeProduit_id']) and isset($_POST['nom']) and isset($_POST['photo']) and isset($_POST['id'])) {
            $donnees = [
                'nom' => htmlspecialchars($_POST['nom']),                    // echapper les entrées
                'typeProduit_id' => htmlspecialchars($req->get('typeProduit_id')),  //$app['request']-> ne focntionne plus
                'prix' => htmlspecialchars($req->get('prix')),
                'photo' => $app->escape($req->get('photo')),  //$req->query->get('photo')-> ne focntionne plus
                'id' => $app->escape($req->get('id'))//$req->query->get('photo')
            ];
            if ((! preg_match("/^[A-Za-z ]{2,}/",$donnees['nom']))) $erreurs['nom']='nom composé de 2 lettres minimum';
            if(! is_numeric($donnees['typeProduit_id']))$erreurs['typeProduit_id']='veuillez saisir une valeur';
            if(! is_numeric($donnees['prix']))$erreurs['prix']='saisir une valeur numérique';
            if (! preg_match("/[A-Za-z0-9]{2,}.(jpeg|jpg|png)/",$donnees['photo'])) $erreurs['photo']='nom de fichier incorrect (extension jpeg , jpg ou png)';
            if(! is_numeric($donnees['id']))$erreurs['id']='saisir une valeur numérique';
            $contraintes = new Assert\Collection(
                [
                    'id' => [new Assert\NotBlank(),new Assert\Type('digit')],
                    'typeProduit_id' => [new Assert\NotBlank(),new Assert\Type('digit')],
                    'nom' => [
                        new Assert\NotBlank(['message'=>'saisir une valeur']),
                        new Assert\Length(['min'=>2, 'minMessage'=>"Le nom doit faire au moins {{ limit }} caractères."])
                    ],
                    //http://symfony.com/doc/master/reference/constraints/Regex.html
                    'photo' => [
                        new Assert\Length(array('min' => 5)),
                        new Assert\Regex([ 'pattern' => '/[A-Za-z0-9]{2,}.(jpeg|jpg|png)/',
                        'match'   => true,
                        'message' => 'nom de fichier incorrect (extension jpeg , jpg ou png)' ]),
                    ],
                    'prix' => new Assert\Type(array(
                        'type'    => 'numeric',
                        'message' => 'La valeur {{ value }} n\'est pas valide, le type est {{ type }}.',
                    ))
                ]);
            $errors = $app['validator']->validate($donnees,$contraintes);  // ce n'est pas validateValue

            if (count($errors) > 0 && !empty($erreurs)) {
                $this->typeProduitModel = new TypeProduitModel($app);
                $typeProduits = $this->typeProduitModel->getAllTypeProduits();
                return $app["twig"]->render('backOff/Produit/editProduit.html.twig',['donnees'=>$donnees,'errors'=>$errors,'erreurs'=>$erreurs,'typeProduits'=>$typeProduits]);
            }
            else
            {
                $this->ProduitModel = new ProduitModel($app);
                $this->ProduitModel->updateProduit($donnees);
                return $app->redirect($app["url_generator"]->generate("produit.index"));
            }

        }
        else
            return $app->abort(404, 'error Pb id form edit');

    }

    public function trierProduit (Application $app)
    {
        if ( isset($_POST['typeProduit_id'])){
            $donnees['typeProduit_id']=htmlentities($_POST['typeProduit_id']);
        }
        if(! is_numeric($donnees['typeProduit_id']))$erreurs['typeProduit_id']='veuillez saisir une valeur';

        if(! empty($erreurs))
        {
            $this->produitModel = new ProduitModel($app);
            $produits = $this->produitModel->getAllProduits();
            $this->typeProduitModel = new TypeProduitModel($app);
            $typeProduits = $this->typeProduitModel->getAllTypeProduits();
            $this->panierModel = new PanierModel($app);
            $this->userModel=new UserModel($app);
            $id=$this->userModel->recupererId($app);
            $panier = $this->panierModel->readUnPanier($id);
            return $app["twig"]->render('frontOff/Produit/showProduitsClient.html.twig',['data'=>$produits ,'panier'=>$panier,'typeProduits'=>$typeProduits]);

        }
        else
        {
            $this->produitModel = new ProduitModel($app);
            $produits = $this->produitModel->getAllProduits();
            $this->typeProduitModel = new TypeProduitModel($app);
            $typeProduits = $this->typeProduitModel->getAllTypeProduits();
            $this->panierModel = new PanierModel($app);
            $this->userModel=new UserModel($app);
            $id=$this->userModel->recupererId($app);
            $panier = $this->panierModel->readUnPanier($id);
            return $app["twig"]->render('frontOff/Produit/showProduitsClient.html.twig',['donnees'=>$donnees,'data'=>$produits ,'panier'=>$panier,'typeProduits'=>$typeProduits]);
        }
    }
    public function detailProduit (Application $app , $id){

        $this->produitModel=new ProduitModel($app);
        $produit= $this->produitModel->getProduit($id);
        //print_r($produit);
        return $app["twig"]->render('frontOff/Produit/detailsProduit.html.twig',['produit'=>$produit]);
    }

    public function reapProduit(Application $app , $id){
        $this->produitModel=new ProduitModel($app);
        $produit= $this->produitModel->getProduit($id);
        //print_r($produit);
        return $app["twig"]->render('backOff/Produit/reap.html.twig',['donnees'=>$produit]);

    }

    public function formReapPorduit(Application $app){


        if(isset($_POST['stock']) && isset($_POST['idProduit'])){
            $donnees['stock']=htmlspecialchars($_POST['stock']);
            $donnees['id']=htmlspecialchars($_POST['idProduit']);



            if(! is_numeric($donnees['stock']))$erreurs['stock']='veuillez saisir une valeur';


            if(empty($erreurs)){
                $this->produitModel=new ProduitModel($app);
                $this->produitModel->updateStockProduit($donnees['stock'],$donnees['id']);
                return $app->redirect($app["url_generator"]->generate("produit.showProduits"));


            }else {

                return $app["twig"]->render('backOff/Panier/reap.html.twig',['donnees'=>$donnees,'erreurs'=>$erreurs]);
            }
        }
        else{
            return $app->abort(404, 'error Pb id form reap');
        }


    }


    public function connect(Application $app) {  //http://silex.sensiolabs.org/doc/providers.html#controller-providers
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'App\Controller\produitController::index')->bind('produit.index');
        $controllers->get('/show', 'App\Controller\produitController::showProduits')->bind('produit.showProduits');

        $controllers->get('/add', 'App\Controller\produitController::addProduit')->bind('produit.addProduit');
        $controllers->post('/add', 'App\Controller\produitController::validFormAddProduit')->bind('produit.validFormAddProduit');

        $controllers->get('/delete/{id}', 'App\Controller\produitController::deleteProduit')->bind('produit.deleteProduit')->assert('id', '\d+');
        $controllers->delete('/delete', 'App\Controller\produitController::validFormDeleteProduit')->bind('produit.validFormDeleteProduit');

        $controllers->get('/edit/{id}', 'App\Controller\produitController::editProduit')->bind('produit.editProduit')->assert('id', '\d+');
        $controllers->put('/edit', 'App\Controller\produitController::validFormEditProduit')->bind('produit.validFormEditProduit');

        $controllers->get('/showClient', 'App\Controller\produitController::showProduitsClient')->bind('produitClient.show');
        $controllers->post('/trier/', 'App\Controller\produitController::trierProduit')->bind('produit.validTriage');

        $controllers->get('/detailProduit/{id}', 'App\Controller\produitController::detailProduit')->bind('produit.detailProduit');

        $controllers->get('/reap/{id}', 'App\Controller\produitController::reapProduit')->bind('produit.reap');
        $controllers->post('/reap/', 'App\Controller\produitController::formReapPorduit')->bind('produit.Formreap');
        return $controllers;
    }
}
