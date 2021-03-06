<?php
namespace App\Controller;

use App\Model\UserModel;

use Gregwar\Captcha\CaptchaBuilder;
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
            if ((!preg_match("/^[A-Za-z ]{2,}/", $donnees['username']))) $erreurs['username'] = 'username composé de 2 lettres minimum';
            if ((!preg_match("/^[A-Za-z ]{4,}/", $donnees['motdepasse']))) $erreurs['motdepasse'] = 'mdp composé de 4 lettres minimum';
           // if ((!preg_match("/^[A-Za-z ]{2,}/", $donnees['email']))) $erreurs['email'] = 'email composé de 2 lettres minimum';
            if (!filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)) $erreurs['email']= 'email incorrect';
            if ((!preg_match("/^[A-Za-z0-9 ]{2,}/", $donnees['adresse']))) $erreurs['adresse'] = 'adresse composé de 2 lettres minimum';
            if ((!preg_match("/^[A-Za-z ]{2,}/", $donnees['ville']))) $erreurs['ville'] = 'ville composé de 2 lettres minimum';
            if ((!preg_match("/^[0-9]{5,}/", $donnees['code_postal']))) $erreurs['code_postal'] = 'code postal composé de 5 chiffres';
            if ((!preg_match("/^[A-Za-z ]{2,}/", $donnees['nom']))) $erreurs['code_postal'] = 'nom composé de 2 lettres minimum';

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

        public function addUser(Application $app){
            $builder = new CaptchaBuilder();
            $builder->build();
            $_SESSION['phrase'] = $builder -> getPhrase();
            return $app["twig"]->render('frontOff/inscription.html.twig',['image'=>$builder->inline()]);
        }

        public  function validFormaddUser(Application $app){

            if (isset($_POST['nom']) && isset($_POST['code_postal']) and isset($_POST['ville'])
                and isset($_POST['adresse']) && isset($_POST['username'])
                && isset($_POST['motdepasse']) && isset($_POST['email']) && isset($_POST['captcha']) ) {
                $donnees = [
                    'nom' => htmlspecialchars($_POST['nom']),
                    'code_postal' => htmlspecialchars($_POST['code_postal']),
                    'ville' => htmlspecialchars($_POST['ville']),
                    'adresse' => htmlspecialchars($_POST['adresse']),
                    'username' => htmlspecialchars($_POST['username']),
                    'motdepasse' => htmlspecialchars($_POST['motdepasse']),
                    'email' => htmlspecialchars($_POST['email']),
                    'captcha' => htmlspecialchars($_POST['captcha'])
                ];
                if ((!preg_match("/^[A-Za-z0-9 ]{2,}/", $donnees['username']))) $erreurs['username'] = 'username composé de 2 lettres minimum';
                if ((!preg_match("/^[A-Za-z0-9 ]{4,}/", $donnees['motdepasse']))) $erreurs['motdepasse'] = 'mdp composé de 4 lettres minimum';
                if (!filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)) $erreurs['email']= 'email incorrect';
                if ((!preg_match("/^[A-Za-z0-9 ]{2,}/", $donnees['adresse']))) $erreurs['adresse'] = 'adresse composé de 2 lettres minimum';
                if ((!preg_match("/^[A-Za-z ]{2,}/", $donnees['ville']))) $erreurs['ville'] = 'ville composé de 2 lettres minimum';
                if ((!preg_match("/^[0-9]{5,}/", $donnees['code_postal']))) $erreurs['code_postal'] = 'code postal composé de 5 chiffres';
                if ((!preg_match("/^[A-Za-z ]{2,}/", $donnees['nom']))) $erreurs['nom'] = 'nom composé de 2 lettres minimum';
                if($donnees['captcha'] != $_SESSION['phrase']) $erreurs['captcha']='Le captcha est incorrect';

                $this->userModel = new UserModel($app);
                $data=$this->userModel->getalluser();
                foreach ($data as $value){
                    if($donnees['username'] == $value['username']){
                        $erreurs['username']='Cette username est déjà utilisé, veuillez en prendre un autre';
                        break;
                    }
                }

                if(! empty($erreurs))
                {
                    $builder = new CaptchaBuilder();
                    $builder->build();
                    $_SESSION['phrase'] = $builder -> getPhrase();
                    return $app["twig"]->render('frontOff/inscription.html.twig',['donnees'=>$donnees , 'erreurs'=> $erreurs, 'image'=>$builder->inline()]);
                }
                else
                {

                    $grainDeSel = "gsjkstzzeadsfùzrafsdf!sq!fezlkfes";
                    $hash = md5($donnees['motdepasse'].$grainDeSel);
                    $donnees['password'] = $hash;
                    print_r($donnees);
                    $this->userModel = new UserModel($app);
                    $this->userModel->insertUser($donnees);
                    return $app->redirect($app["url_generator"]->generate("accueil"));
                }
            }
            else
                return $app->abort(404, 'error Pb data form Add');

        }
    public function connect(Application $app) {
		$controllers = $app['controllers_factory'];
		$controllers->match('/', 'App\Controller\UserController::index')->bind('user.index');
		$controllers->get('/login', 'App\Controller\UserController::connexionUser')->bind('user.login');
		$controllers->post('/login', 'App\Controller\UserController::validFormConnexionUser')->bind('user.validFormlogin');

        $controllers->get('/add', 'App\Controller\UserController::addUser')->bind('user.add');
        $controllers->post('/add', 'App\Controller\UserController::validFormaddUser')->bind('user.validFormAdduser');

		$controllers->get('/logout', 'App\Controller\UserController::deconnexionSession')->bind('user.logout');
        $controllers->get('/profil', 'App\Controller\UserController::showProfil')->bind('user.profil');

        $controllers->get('/profil/edit/{id}', 'App\Controller\UserController::editProfil')->bind('profil.edit');
        $controllers->put('/profil/edit/', 'App\Controller\UserController::validFormEditProfil')->bind('profil.validFormEditProfil');

		return $controllers;
	}
}