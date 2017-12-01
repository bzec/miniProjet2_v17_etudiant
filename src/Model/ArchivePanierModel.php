<?php

namespace App\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Silex\Application;

class ArchivePanierModel {

    private $db;

    public function __construct(Application $app) {
        $this->db = $app['db'];
    }



    public function ajouterDansArchivagePanier($donnees){
        $queryBuilder = new QueryBuilder($this->db);
       // print_r($donnees);
        $queryBuilder->insert('archivepaniers')
            ->values([
                'panier_id' =>'?',
                'quantite' => '?',
                'prix' => '?',
                'dateAjoutPanier' => '?',
                'user_id' => '?',
                'produit_id' => '?',
                'commande_id'=> '?'
            ])
            ->setParameter(0,$donnees['id'])
            ->setParameter(1, $donnees['quantite'])
            ->setParameter(2, $donnees['prix'])
            ->setParameter(3, $donnees['dateAjoutPanier'])
            ->setParameter(4, $donnees['user_id'])
            ->setParameter(5, $donnees['produit_id'])
            ->setParameter(6, null)
        ;
        //echo $queryBuilder;
        return $queryBuilder->execute();
    }

    public function deleteArchivePanier($id){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->delete('archivepaniers')
            ->where('panier_id='.$id.' and commande_id is null ;')
        ;
        echo $queryBuilder;
        return $queryBuilder->execute();
    }
    public function recupererProduitPanier($donnees){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('pan.produit_id,pan.quantite,pan.id')
            ->from('paniers','pan')
            ->innerJoin('pan','produits','prod','pan.produit_id=prod.id')
            ->where('prod.id='.$donnees['produit_id'].' and pan.user_id='.$donnees['user_id'].'');
        return $queryBuilder->execute()->fetch();
    }

    public function readUnPanierCommande($iduser,$idcom){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('prod.photo','prod.nom','pan.quantite','pan.prix','pan.dateAjoutPanier','pan.produit_id','pan.user_id','pan.id')
            ->from('archivespaniers','pan')
            ->innerJoin('pan','produits','prod','pan.produit_id=prod.id')
            ->innerJoin('pan','commandes','com','pan.produit_id=com.id')
            ->innerJoin('pan','users','us','pan.user_id=us.id')
            ->where('pan.user_id='.$iduser.' and com.id='.$idpcom.'');

        return $queryBuilder->execute()->fetch();
    }
}