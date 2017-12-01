<?php

namespace App\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Silex\Application;

class PanierModel {

    private $db;

    public function __construct(Application $app) {
        $this->db = $app['db'];
    }

    public function readUnPanier($id){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('prod.photo','prod.nom','pan.quantite','pan.prix','pan.dateAjoutPanier','pan.id')
            ->from('paniers','pan')
            ->innerJoin('pan','produits','prod','pan.produit_id=prod.id')
            ->innerJoin('pan','users','us','pan.user_id=us.id')
            ->where('pan.user_id='.$id.' and commande_id is null');

        return $queryBuilder->execute()->fetchAll();

    }

    public function readUnPanierProduit($iduser,$idprod){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('prod.photo','prod.nom','pan.quantite','pan.prix','pan.dateAjoutPanier','pan.produit_id','pan.user_id','pan.id')
            ->from('paniers','pan')
            ->innerJoin('pan','produits','prod','pan.produit_id=prod.id')
            ->innerJoin('pan','users','us','pan.user_id=us.id')
            ->where('pan.user_id='.$iduser.' and commande_id is null and prod.id='.$idprod.'');

        return $queryBuilder->execute()->fetch();
    }



    public function updatePanier($donnees){
        $queryBuilder = new QueryBuilder($this->db);
      $queryBuilder->update('paniers')
            ->set('quantite','"'.$donnees['quantite'].'"')
            ->set('dateAjoutPanier', '"'.$donnees['dateAjoutPanier'].'"')
            ->set('prix', ''.$donnees['prix'].'')
            ->set('user_id', '"'.$donnees['user_id'].'"')
            ->set('produit_id', '"'.$donnees['produit_id'].'"')
            ->set('commande_id', 'null')
            ->where('id='.$donnees['id'].'')
        ;
        echo $queryBuilder;
        return $queryBuilder->execute();
    }

    public function readUnPanierSuppr($id){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('prod.photo','prod.nom','pan.quantite','pan.prix','pan.dateAjoutPanier','pan.id')
            ->from('paniers','pan')
            ->innerJoin('pan','produits','prod','pan.produit_id=prod.id')
            ->where('pan.id='.$id.'');
        return $queryBuilder->execute()->fetch();

    }

    public function deletePanier($id){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->delete('paniers')
            ->where('id='.$id.'')
        ;
        return $queryBuilder->execute();
    }

    public function deleteUserPanier($id){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->delete('paniers')
            ->where('user_id='.$id.'')
        ;
        return $queryBuilder->execute();
    }

    public function ajouterDansPanier($donnees){
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->insert('paniers')
            ->values([
                'quantite' => '?',
                'prix' => '?',
                'dateAjoutPanier' => '?',
                'user_id' => '?',
                'produit_id' => '?',
                'commande_id'=> '?'
            ])
            ->setParameter(0, $donnees['quantite'])
            ->setParameter(1, $donnees['prix'])
            ->setParameter(2, $donnees['dateAjoutPanier'])
            ->setParameter(3, $donnees['user_id'])
            ->setParameter(4, $donnees['produit_id'])
            ->setParameter(5, null)
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

}