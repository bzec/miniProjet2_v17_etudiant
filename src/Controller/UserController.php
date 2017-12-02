<?php
namespace App\Controller;

use App\Model\UserModel;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;   // modif version 2.0

use Symfony\Component\HttpFoundation\Request;   // pour utiliser request

class UserController implements ControllerProviderInterface {

	private $userModel;

	public function index(Application $app) {
		return $this->connexionUser($app);
	}

	public function connexionUser(Application $app)
	{
		return $app["twig"]->render('login.html.twig');
	}

	public function validFormConnexionUser(Application $app, Request $req)
	{

		$app['session']->clear();
		$donnees['login']=$req->get('login');
		$donnees['password']=$req->get('password');

		$this->userModel = new UserModel($app);
		$data=$this->userModel->verif_login_mdp_Utilisateur($donnees['login'],$donnees['password']);

		if($data != NULL)
		{
			$app['session']->set('roles', $data['roles']);  //dans twig {{ app.session.get('roles') }}
			$app['session']->set('username', $data['username']);
			$app['session']->set('logged', 1);
			$app['session']->set('user_id', $data['id']);
			return $app->redirect($app["url_generator"]->generate("accueil"));
		}
		else
		{
			$app['session']->set('erreur','mot de passe ou login incorrect');
			return $app["twig"]->render('login.html.twig');
		}
	}
	public function deconnexionSession(Application $app)
	{
		$app['session']->clear();
		$app['session']->getFlashBag()->add('msg', 'vous êtes déconnecté');
		return $app->redirect($app["url_generator"]->generate("accueil"));
	}

    public function showProfil(Application $app)
    {
        $this->userModel = new UserModel($app);
        $donnees=$this->userModel->getUser($this->userModel->recupererId($app));
        //print_r($donnees);
        //print_r($donnees);
        return $app["twig"]->render('frontOff/monProfil.html.twig',['user'=>$donnees]);
    }

    public function editProfil(Application $app, $id)
    {
        $this->userModel = new UserModel($app);
        $donnees=$this->userModel->getUser($this->userModel->recupererId($app));
        //print_r($donnees);
        return $app["twig"]->render('frontOff/editProfil.html.twig',['donnees'=>$donnees]);
    }

    public function validFormEditProfil(Application$app){
        if(isset($_POST['username']) and isset($_POST['id']) and isset($_POST['motdepasse']) and isset($_POST['email']) and isset($_POST['nom'])
            and isset($_POST['code_postal']) and  isset($_POST['ville']) and isset($_POST['adresse'])) {
           print($_POST['motdepasse']);
            $donnees = [
                'username' => htmlspecialchars($_POST['username']),
                'motdepasse' => htmlspecialchars($_POST['motdepasse']),
                'email' => htmlspecialchars($_POST['email']),
                'nom' => htmlentities($_POST['nom']),
                'ville' => htmlentities($_POST['ville']),
                'code_postal' => htmlentities($_POST['code_postal']),
                'adresse' => htmlentities($_POST['adresse']),
                'id'=>htmlentities($_POST['id'])
            ];
            if ((!preg_match("/^[A-Za-z ]{2,}/", $donnees['username']))) $erreurs['username'] = 'nom composé de 2 lettres minimum';
            if ((!preg_match("/^[A-Za-z ]{2,}/", $donnees['motdepasse']))) $erreurs['motdepasse'] = 'nom composé de 2 lettres minimum';
            if ((!preg_match("/^[A-Za-z ]{2,}/", $donnees['email']))) $erreurs['email'] = 'nom composé de 2 lettres minimum';
            if (!empty($erreurs)) {

                return $app["twig"]->render('frontOff/editProfil.html.twig', ['donnees' => $donnees, 'erreurs' => $erreurs]);
            } else {
                $this->userModel = new UserModel($app);
                $this->userModel->updateProfil($donnees);
                return $app->redirect($app["url_generator"]->generate("user.profil"));
            }
        }
        else{
            return $app->abort(404, 'error Pb data form Add');
        }

        }

    public function connect(Application $app) {
		$controllers = $app['controllers_factory'];
		$controllers->match('/', 'App\Controller\UserController::index')->bind('user.index');
		$controllers->get('/login', 'App\Controller\UserController::connexionUser')->bind('user.login');
		$controllers->post('/login', 'App\Controller\UserController::validFormConnexionUser')->bind('user.validFormlogin');

		$controllers->get('/logout', 'App\Controller\UserController::deconnexionSession')->bind('user.logout');
        $controllers->get('/profil', 'App\Controller\UserController::showProfil')->bind('user.profil');

        $controllers->get('/profil/edit/{id}', 'App\Controller\UserController::editProfil')->bind('profil.edit');
        $controllers->put('/profil/edit/', 'App\Controller\UserController::validFormEditProfil')->bind('profil.validFormEditProfil');

		return $controllers;
	}
}