<?php

namespace App\Database;
use PDO;

class Database {
    private PDO $pdo;

    //Constructeur de la classe Database qui initialise la connexion à la base de données
    public function __construct() {
        //Récupération des informations de connexion à la base de données depuis les variables d'environnement
        $host = 'db';
        $dbname   = getenv('MARIADB_DATABASE');
        $user = getenv('MARIADB_USER');
        $password = getenv('MARIADB_PASSWORD');

        // Connexion à la base de données avec PDO
        $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);

        // Configuration de PDO pour lancer des exceptions en cas d'erreur
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Méthode pour récupérer la connexion PDO
    public function getConnection() {
        return $this->pdo;
    }
}