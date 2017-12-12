<?php

namespace App\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Silex\Application;

class ProduitModel {

    private $db;

    public function __construct(Application $app) {
        $this->db = $app['db'];
    }
    // http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html#join-clauses
    public function getAllProduits() {

        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('p.id', 't.libelle', 'p.nom', 'p.prix', 'p.photo','p.stock','p.typeProduit_id')
            ->from('produits', 'p')
            ->innerJoin('p', 'typeProduits', 't', 'p.typeProduit_id=t.id')
            ->addOrderBy('p.stock', 'ASC');
        return $queryBuilder->execute()->fetchAll();

    }

    public function insertProduit($donnees) {
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder->insert('produits')
            ->values([
                'nom' => '?',
                'typeProduit_id' => '?',
                'prix' => '?',
                'photo' => '?',
                'dispo' => '0',  //à modifier
                'stock' => '0'
            ])
            ->setParameter(0, $donnees['nom'])
            ->setParameter(1, $donnees['typeProduit_id'])
            ->setParameter(2, $donnees['prix'])
            ->setParameter(3, $donnees['photo'])
        ;
        return $queryBuilder->execute();
    }

    function getProduit($id) {
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->select('p.id', 'p.typeProduit_id', 'p.nom', 'p.prix', 'p.photo','p.stock','t.libelle')
            ->from('produits ','p')
            ->innerJoin('p', 'typeProduits', 't', 'p.typeProduit_id=t.id')
            ->where('p.id= :id')
            ->setParameter('id', $id);
        return $queryBuilder->execute()->fetch();
    }

    public function updateProduit($donnees) {
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->update('produits')
            ->set('nom', '?')
            ->set('typeProduit_id','?')
            ->set('prix','?')
            ->set('photo','?')
            ->where('id= ?')
            ->setParameter(0, $donnees['nom'])
            ->setParameter(1, $donnees['typeProduit_id'])
            ->setParameter(2, $donnees['prix'])
            ->setParameter(3, $donnees['photo'])
            ->setParameter(4, $donnees['id']);

        return $queryBuilder->execute();


    }

    public function deleteProduit($id) {
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->delete('produits')
            ->where('id = :id')
            ->setParameter('id',(int)$id)
        ;
        return $queryBuilder->execute();
    }

    public function updateStockProduit($quantite,$id)
    {
        $queryBuilder = new QueryBuilder($this->db);
        $queryBuilder
            ->update('produits')
            ->set('stock', '?')
            ->where('id= ?')
            ->setParameter(0, $quantite)
            ->setParameter(1, $id);

        return $queryBuilder->execute();
    }


}