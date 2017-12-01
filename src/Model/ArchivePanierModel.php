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
        return $queryBuilder->execute();
    }

    public function readPanierCommande($iduser, $idcom){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('prod.photo','prod.nom','pan.quantite','pan.prix','pan.dateAjoutPanier','pan.produit_id','pan.user_id','pan.id')
            ->from('archivepaniers','pan')
            ->innerJoin('pan','users','us','pan.user_id=us.id')
            ->innerJoin('pan','produits','prod','pan.produit_id=prod.id')
            ->innerJoin('pan','commandes','com','pan.commande_id=com.id')
            ->where('pan.user_id='.$iduser.' and pan.commande_id='.$idcom.';');
        //echo $queryBuilder;
        return $queryBuilder->execute()->fetchAll();
    }

    public function readPanierCommandeVendeur($idcom){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('us.username','prod.photo','prod.nom','pan.quantite','pan.prix','pan.dateAjoutPanier','pan.produit_id','pan.user_id','pan.id')
            ->from('archivepaniers','pan')
            ->innerJoin('pan','users','us','pan.user_id=us.id')
            ->innerJoin('pan','produits','prod','pan.produit_id=prod.id')
            ->innerJoin('pan','commandes','com','pan.commande_id=com.id')
            ->where( 'pan.commande_id='.$idcom.';');
        //echo $queryBuilder;
        return $queryBuilder->execute()->fetchAll();
    }
    public function deleteArchivePanier($id){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->delete('archivepaniers')
            ->where('panier_id='.$id.' and commande_id is null ;')
        ;
        //echo $queryBuilder;
        return $queryBuilder->execute();
    }



    public function updateArchivePanier($donnees)
    {
        print_r($donnees);
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->update('archivepaniers')
            ->set('quantite',''.$donnees['quantite'].'')
            ->set('dateAjoutPanier', '"'.$donnees['dateAjoutPanier'].'"')
            ->set('prix', ''.$donnees['prix'].'')
            ->set('user_id', ''.$donnees['user_id'].'')
            ->set('produit_id', ''.$donnees['produit_id'].'')
            ->set('commande_id', 'null')
            ->where('id='.$donnees['id'].'')
        ;
        //echo $queryBuilder;
        return $queryBuilder->execute();

    }

}