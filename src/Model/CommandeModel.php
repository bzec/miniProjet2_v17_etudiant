<?php

namespace App\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Silex\Application;

class CommandeModel
{

    private $db;

    public function __construct(Application $app)
    {
        $this->db = $app['db'];
    }


    public function ajoutCommande($donnees){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->insert('commandes')
            ->values([
                'user_id' => '?',
                'prix' => '?',
                'date_achat' => '?',
                'etat_id' => '?'
            ])
            ->setParameter(0, $donnees['user_id'])
            ->setParameter(1, $donnees['prix'])
            ->setParameter(2, $donnees['date_achat'])
            ->setParameter(3, $donnees['etat_id'])
        ;
       // echo $queryBuilder;
        return $queryBuilder->execute();
    }

    public function getCommandeClient($iduser){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('com.id,com.user_id,com.prix,com.date_achat,com.etat_id,e.libelle')
            ->from('Commandes','com')
            ->innerJoin('com','users','u','com.user_id=u.id')
            ->innerJoin('com','etats','e','com.etat_id=e.id')
            ->where('u.id='.$iduser.'');
        return $queryBuilder->execute()->fetchAll();
    }

    public function updateEtatCommande($idcom){

        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->update('commandes')
            ->set('etat_id','2')
            ->where('id='.$idcom.'')
        ;
        //echo $queryBuilder;
        return $queryBuilder->execute();
    }

    public function deleteCommande($id){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->delete('commandes')
            ->where('id='.$id.'')
        ;
        return $queryBuilder->execute();
    }

    public function getAllCommande(){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('com.id,com.user_id,com.prix,com.date_achat,com.etat_id,e.libelle','u.username')
            ->from('Commandes','com')
            ->innerJoin('com','users','u','com.user_id=u.id')
            ->innerJoin('com','etats','e','com.etat_id=e.id')
            ;
        return $queryBuilder->execute()->fetchAll();
    }
    public function transaction($user){
        $user_id=$user;
        echo $user_id;
        $conn = $this->db;
        $conn->beginTransaction();
        $requestSQL = $conn->prepare('select sum(prix) as prix from paniers WHERE user_id = :idUser and commande_id is Null');
        $requestSQL->execute(['idUser'=>$user_id]);
        $prix = $requestSQL->fetch()['prix'];
        echo $prix;
        $conn->commit();
        $conn->beginTransaction();
        $requestSQL = $conn->prepare('insert into commandes(user_id, prix, etat_id) values (?,?,?)');
        $requestSQL->execute([$user_id, $prix, 1]);
        $lastinsertid=$conn->lastInsertId();
        $requestSQL=$conn->prepare('update paniers set commande_id=? where user_id=? and commande_id is null');
        $requestSQL->execute([$lastinsertid, $user_id]);
        $requestSQL=$conn->prepare('update archivepaniers set commande_id=? where user_id=? and commande_id is null');
        $requestSQL->execute([$lastinsertid, $user_id]);
        $conn->commit();

    }

}