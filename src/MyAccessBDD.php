<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {

    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesDocument($champs);
            case "commandedocument" :
                return $this->selectCommandesDocument($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|array|null nombre de tuples ajoutés ou null si erreur
     * @override
     */
    protected function traitementInsert(string $table, ?array $champs) : int|array|null{
        switch($table){
            case "livre":
                return $this->insertLivre($champs);
            case "dvd":
                return $this->insertDvd($champs);
            case "revue":
                return $this->insertRevue($champs);
            default:
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|array|null nombre de tuples modifiés ou null si erreur
     * @override
     */
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : int|array|null{
        switch($table){
            case "livre" :
                return $this->updateLivre($champs);
            case "dvd" :
                return $this->updateDvd($champs);
            case "revue" :
                return $this->updateRevue($champs);
            default:
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }
    }
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "livre" :
                return $this->supprimerLivre($champs);
            case "dvd" :
                return $this->supprimerDvd($champs);
            case "revue" :
                return $this->supprimerRevue($champs);
            default:
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);
        }
    }
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);
            return $this->conn->queryBDD($requete, $champs);
        }
    }

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * Insertion d'un nouveau document dans la base de données
     * @param $id
     * @param $titre
     * @param $image
     * @param $idRayon
     * @param $idPublic
     * @param $idGenre
     * @return int|null Le retour direct de l'appel à la méthode Connexion->updateBDD
     */
    private function insertDocument($id, $titre, $image, $idRayon, $idPublic, $idGenre): ?int {
        $requete = "insert into document values(:id, :titre, :image, :idRayon, :idPublic, :idGenre);";
        return $this->conn->updateBDD($requete, [
            'id' => $id,
            'titre' => $titre,
            'image' => $image,
            'idRayon' => $idRayon,
            'idPublic' => $idPublic,
            'idGenre' => $idGenre
        ]);
    }

    /**
     * Mise à jour d'un document existant dans la base de données
     * @param $id
     * @param $titre
     * @param $image
     * @param $idRayon
     * @param $idPublic
     * @param $idGenre
     * @return int|null Le retour direct de l'appel à la méthode Connexion->updateBDD
     */
    private function updateDocument($id, $titre, $image, $idRayon, $idPublic, $idGenre): ?int {
        $requete = "update document ";
        $requete .= "set titre = :titre, image = :image, idRayon = :idRayon, idPublic = :idPublic, idGenre = :idGenre ";
        $requete .= "where id = :id;";
        return $this->conn->updateBDD($requete, [
            'id' => $id,
            'titre' => $titre,
            'image' => $image,
            'idRayon' => $idRayon,
            'idPublic' => $idPublic,
            'idGenre' => $idGenre
        ]);
    }

    /**
     * Supprime un document de la table document
     * @param string $id L'identifiant du document à supprimer
     * @return int|null Le retour direct de l'appel à Connexion->updateBDD
     */
    private function supprimerDocument(string $id) : ?int {
        return $this->conn->updateBDD('DELETE FROM document WHERE id = :id;', ['id' => $id]);
    }

    /**
     * Insertion d'un nouveau livre dans la base de données
     * @param array|null $champs Les champs de la requête contenant tous les champs nécessaires à la création d'un livre
     * @return array[]|null Les champs passés en paramètres en cas d'insertion réussie ou null en cas d'erreur
     */
    private function insertLivre(?array $champs): array|null {
        if (empty($champs)){
            return null;
        }

        if (!$this->conn->beginTransaction()) {
            return null;
        }

        if (!$this->insertDocument($champs['Id'], $champs['Titre'], $champs['Image'], $champs['IdRayon'], $champs['IdPublic'], $champs['IdGenre'])) {
            $this->conn->rollback();
            return null;
        }

        $requete = 'insert into livres_dvd values(:id);';
        if(!$this->conn->updateBDD($requete, ['id' => $champs['Id']])) {
            $this->conn->rollback();
            return null;
        }

        $requete = 'insert into livre values(:id, :isbn, :auteur, :collection);';
        if(!$this->conn->updateBDD($requete, [
            'id' => $champs['Id'],
            'isbn' => $champs['Isbn'],
            'auteur' => $champs['Auteur'],
            'collection' => $champs['Collection']
        ])) {
            $this->conn->rollback();
            return null;
        }

        $this->conn->commit();
        return [$champs];
    }

    /**
     * Mise à jour d'un livre existant dans la base de données
     * @param array|null $champs Les champs de la requête contenant tous les nouveaux champs nécessaires à la mise à jour d'un livre
     * @return array[]|null Les champs passés en paramètres en cas de mise à jour réussie ou null en cas d'erreur
     */
    private function updateLivre(?array $champs): array|null {
        if (empty($champs)){
            return null;
        }

        if (!$this->conn->beginTransaction()) {
            return null;
        }

        $resultatUpdate = $this->updateDocument($champs['Id'], $champs['Titre'], $champs['Image'], $champs['IdRayon'], $champs['IdPublic'], $champs['IdGenre']);
        if (!isset($resultatUpdate)) {
            $this->conn->rollback();
            return null;
        }

        $requete = 'update livre ';
        $requete .= 'set ISBN = :isbn, auteur = :auteur, collection = :collection ';
        $requete .= 'where id = :id;';
        $resultatUpdate = $this->conn->updateBDD($requete, [
            'id' => $champs['Id'],
            'isbn' => $champs['Isbn'],
            'auteur' => $champs['Auteur'],
            'collection' => $champs['Collection']
        ]);
        if (!isset($resultatUpdate)) {
            $this->conn->rollback();
            return null;
        }

        $this->conn->commit();
        return [$champs];
    }

    /**
     * Suppression à jour d'un livre dans la base de données
     * @param array|null $champs Les champs de la requête contenant le champ 'id' pour l'identifiant du livre
     * @return int|null Le nombre de lignes supprimées en cas de réussite ou null en cas d'erreur
     */
    private function supprimerLivre(?array $champs): ?int {
        if (empty($champs['Id'])){
            return null;
        }

        if (!$this->conn->beginTransaction()) {
            return null;
        }

        $resultatDelete = $this->conn->updateBDD('DELETE FROM livre WHERE id = :id;', ['id' => $champs['Id']]);
        if (!isset($resultatDelete)) {
            $this->conn->rollback();
            return null;
        }

        $resultatDelete = $this->supprimerLivreDvd($champs['Id']);
        if (!isset($resultatDelete)) {
            $this->conn->rollback();
            return null;
        }

        $resultatDelete = $this->supprimerDocument($champs['Id']);
        if (!isset($resultatDelete)) {
            $this->conn->rollback();
            return null;
        }

        $this->conn->commit();
        return $resultatDelete;
    }

    /**
     * Insertion d'un nouveau DVD dans la base de données
     * @param array|null $champs Les champs de la requête contenant tous les champs nécessaires à la création d'un DVD
     * @return array[]|null Les champs passés en paramètres en cas d'insertion réussie ou null en cas d'erreur
     */
    private function insertDvd(?array $champs): array|null {
        if (empty($champs)){
            return null;
        }

        if (!$this->conn->beginTransaction()) {
            return null;
        }

        if (!$this->insertDocument($champs['Id'], $champs['Titre'], $champs['Image'], $champs['IdRayon'], $champs['IdPublic'], $champs['IdGenre'])) {
            $this->conn->rollback();
            return null;
        }

        $requete = 'insert into livres_dvd values(:id);';
        if(!$this->conn->updateBDD($requete, ['id' => $champs['Id']])) {
            $this->conn->rollback();
            return null;
        }

        $requete = 'insert into dvd values(:id, :synopsis, :realisateur, :duree);';
        if(!$this->conn->updateBDD($requete, [
            'id' => $champs['Id'],
            'synopsis' => $champs['Synopsis'],
            'realisateur' => $champs['Realisateur'],
            'duree' => $champs['Duree']
        ])) {
            $this->conn->rollback();
            return null;
        }

        $this->conn->commit();
        return [$champs];
    }

    /**
     * Mise à jour d'un DVD existant dans la base de données
     * @param array|null $champs Les champs de la requête contenant tous les nouveaux champs nécessaires à la mise à jour d'un DVD
     * @return array[]|null Les champs passés en paramètres en cas de mise à jour réussie ou null en cas d'erreur
     */
    private function updateDvd(?array $champs): array|null {
        if (empty($champs)){
            return null;
        }

        if (!$this->conn->beginTransaction()) {
            return null;
        }

        $resultatUpdate = $this->updateDocument($champs['Id'], $champs['Titre'], $champs['Image'], $champs['IdRayon'], $champs['IdPublic'], $champs['IdGenre']);
        if (!isset($resultatUpdate)) {
            $this->conn->rollback();
            return null;
        }

        $requete = 'update dvd ';
        $requete .= 'set synopsis = :synopsis, realisateur = :realisateur, duree = :duree ';
        $requete .= 'where id = :id;';
        $resultatUpdate = $this->conn->updateBDD($requete, [
            'id' => $champs['Id'],
            'synopsis' => $champs['Synopsis'],
            'realisateur' => $champs['Realisateur'],
            'duree' => $champs['Duree']
        ]);
        if (!isset($resultatUpdate)) {
            $this->conn->rollback();
            return null;
        }

        $this->conn->commit();
        return [$champs];
    }

    /**
     * Suppression à jour d'un DVD dans la base de données
     * @param array|null $champs Les champs de la requête contenant le champ 'id' pour l'identifiant du DVD
     * @return int|null Le nombre de lignes supprimées en cas de réussite ou null en cas d'erreur
     */
    private function supprimerDvd(?array $champs): ?int {
        if (empty($champs['Id'])){
            return null;
        }

        if (!$this->conn->beginTransaction()) {
            return null;
        }

        $resultatDelete = $this->conn->updateBDD('DELETE FROM dvd WHERE id = :id;', ['id' => $champs['Id']]);
        if (!isset($resultatDelete)) {
            $this->conn->rollback();
            return null;
        }

        $resultatDelete = $this->supprimerLivreDvd($champs['Id']);
        if (!isset($resultatDelete)) {
            $this->conn->rollback();
            return null;
        }

        $resultatDelete = $this->supprimerDocument($champs['Id']);
        if (!isset($resultatDelete)) {
            $this->conn->rollback();
            return null;
        }

        $this->conn->commit();
        return $resultatDelete;
    }

    /**
     * Supprime un livre / DVD de la table livres_dvd
     * @param string $id L'identifiant de l'enregistrement a supprimer
     * @return int|null Le retour direct de l'appel à Connexion->updateBDD
     */
    private function supprimerLivreDvd(string $id): int|null {
        return $this->conn->updateBDD('DELETE FROM livres_dvd WHERE id = :id;', ['id' => $id]);
    }

    /**
     * Insertion d'une nouvelle revue dans la base de données
     * @param array|null $champs Les champs de la requête contenant tous les champs nécessaires à la création d'une revue
     * @return array[]|null Les champs passés en paramètres en cas d'insertion réussie ou null en cas d'erreur
     */
    private function insertRevue(?array $champs): array|null {
        if (empty($champs)){
            return null;
        }

        if (!$this->conn->beginTransaction()) {
            return null;
        }

        if (!$this->insertDocument($champs['Id'], $champs['Titre'], $champs['Image'], $champs['IdRayon'], $champs['IdPublic'], $champs['IdGenre'])) {
            $this->conn->rollback();
            return null;
        }

        $requete = 'insert into revue values(:id, :periodicite, :delaiMiseADispo);';
        if(!$this->conn->updateBDD($requete, [
            'id' => $champs['Id'],
            'periodicite' => $champs['Periodicite'],
            'delaiMiseADispo' => $champs['DelaiMiseADispo']
        ])) {
            $this->conn->rollback();
            return null;
        }

        $this->conn->commit();
        return [$champs];
    }

    /**
     * Mise à jour d'une revue existante dans la base de données
     * @param array|null $champs Les champs de la requête contenant tous les nouveaux champs nécessaires à la mise à jour d'une revue
     * @return array[]|null Les champs passés en paramètres en cas de mise à jour réussie ou null en cas d'erreur
     */
    private function updateRevue(?array $champs): array|null {
        if (empty($champs)){
            return null;
        }

        if (!$this->conn->beginTransaction()) {
            return null;
        }

        $resultatUpdate = $this->updateDocument($champs['Id'], $champs['Titre'], $champs['Image'], $champs['IdRayon'], $champs['IdPublic'], $champs['IdGenre']);
        if (!isset($resultatUpdate)) {
            $this->conn->rollback();
            return null;
        }

        $requete = 'update revue ';
        $requete .= 'set periodicite = :periodicite, delaiMiseADispo = :delaiMiseADispo ';
        $requete .= 'where id = :id;';
        $resultatUpdate = $this->conn->updateBDD($requete, [
            'id' => $champs['Id'],
            'periodicite' => $champs['Periodicite'],
            'delaiMiseADispo' => $champs['DelaiMiseADispo']
        ]);
        if (!isset($resultatUpdate)) {
            $this->conn->rollback();
            return null;
        }

        $this->conn->commit();
        return [$champs];
    }

    /**
     * Suppression à jour d'une revue dans la base de données
     * @param array|null $champs Les champs de la requête contenant le champ 'id' pour l'identifiant de la revue
     * @return int|null Le nombre de lignes supprimées en cas de réussite ou null en cas d'erreur
     */
    private function supprimerRevue(?array $champs): ?int {
        if (empty($champs['Id'])){
            return null;
        }

        if (!$this->conn->beginTransaction()) {
            return null;
        }

        $resultatDelete = $this->conn->updateBDD('DELETE FROM revue WHERE id = :id;', ['id' => $champs['Id']]);
        if (!isset($resultatDelete)) {
            $this->conn->rollback();
            return null;
        }

        $resultatDelete = $this->supprimerDocument($champs['Id']);
        if (!isset($resultatDelete)) {
            $this->conn->rollback();
            return null;
        }

        $this->conn->commit();
        return $resultatDelete;
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
        if(empty($champs)){
            return null;
        }
        if(is_null($id)){
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $champs["id"] = $id;
        $requete .= " where id=:id;";
        return $this->conn->updateBDD($requete, $champs);
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);
        return $this->conn->updateBDD($requete, $champs);
    }
 
    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";
        return $this->conn->queryBDD($requete);
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère tous les exemplaires d'un document
     * @param array|null $champs
     * @return array|null
     */
    private function selectExemplairesDocument(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Retourne toutes les commandes pour un livre / DVD
     * @param array|null $champs Les champs contenant un champ 'id' avec l'identifiant du document
     * @return array|null La liste des commandes du document ou null en cas d'erreur
     */
    private function selectCommandesDocument(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select * ";
        $requete .= "from commandedocument ";
        $requete .= "where idLivreDvd = :id ";
        $requete .= "order by id ASC;";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }
}
