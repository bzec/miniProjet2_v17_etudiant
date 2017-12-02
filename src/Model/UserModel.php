<?php
namespace App\Model;

use Silex\Application;
use Doctrine\DBAL\Query\QueryBuilder;;

class UserModel {

	private $db;

	public function __construct(Application $app) {
		$this->db = $app['db'];
	}

	public function verif_login_mdp_Utilisateur($login,$mdp){
		$sql = "SELECT id,username,motdepasse,roles FROM users WHERE username = ? AND motdepasse = ?";
		$res=$this->db->executeQuery($sql,[$login,$mdp]);   //md5($mdp);
		if($res->rowCount()==1)
			return $res->fetch();
		else
			return false;
	}
	// public function verif_login_mdp_Utilisateur($login,$mdp){
	// 	$sql = "SELECT id,login,password,droit FROM users WHERE login = ? AND password = ?";
	// 	$res=$this->db->executeQuery($sql,[$login,$mdp]);   //md5($mdp);
	// 	if($res->rowCount()==1)
	// 		return $res->fetch();
	// 	else
	// 		return false;
	// }

    public function recupererId(Application $app){
        return $app['session']->get('user_id');
    }

    public function updateProfil($donnees){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->update('users')
            ->set('username','"'.$donnees['username'].'"')
            ->set('motdepasse', '"'.$donnees['motdepasse'].'"')
            ->set('email', '"'.$donnees['email'].'"')
            ->set('nom', '"'.$donnees['nom'].'"')
            ->set('code_postal', '"'.$donnees['code_postal'].'"')
            ->set('ville', '"'.$donnees['ville'].'"')
            ->set('adresse', '"'.$donnees['adresse'].'"')

            ->where('id='.$donnees['id'].'')
        ;
        echo $queryBuilder;
        return $queryBuilder->execute();

    }
	public function getuser($user_id) {
		$queryBuilder = new QueryBuilder($this->db);
		$queryBuilder
			->select('*')
			->from('users')
			->where('id ='.$user_id.';');
		//echo $queryBuilder;
		return $queryBuilder->execute()->fetch();

	}
}