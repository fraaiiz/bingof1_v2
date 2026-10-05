<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

use App\Router\Router;
use App\Controller\HomeController;
use App\Controller\MonBingoController;
use App\Controller\LesBingosController;
use App\Controller\InfosController;
use App\Controller\CalendrierController;
use App\Controller\ClassementController;
use App\Controller\ResultatController;
use App\Controller\EditResultatController;
use App\Controller\LoginController;
use App\Controller\PredictionController;
use App\Controller\RegisterController;

$router = new Router();

$router->get('/', [new HomeController(), 'index']);
$router->post('/predictions', [new PredictionController(), 'store']);

$router->get('/mon-bingo', [new MonBingoController(), 'index']);
$router->get('/les-bingos', [new LesBingosController(), 'index']);

$router->get('/infos', [new InfosController(), 'redirectToCurrentSeason']);
$router->get('/calendrier', [new CalendrierController(), 'redirectToCurrentSeason']);
$router->get('/classement', [new ClassementController(), 'redirectToCurrentSeason']);

$router->get('/saisons/{annee}/infos', [new InfosController(), 'index']);
$router->get('/saisons/{annee}/calendrier', [new CalendrierController(), 'index']);
$router->get('/saisons/{annee}/classement', [new ClassementController(), 'index']);
$router->get('/saisons/{annee}/courses/{id}/resultats', [new ResultatController(), 'index']);
$router->get('/saisons/{annee}/courses/{id}/resultats/edition', [new EditResultatController(), 'index']);
$router->post('/saisons/{annee}/courses/{id}/resultats/edition', [new EditResultatController(), 'store']);

$router->get('/login', [new LoginController(), 'index']);
$router->post('/login', [new LoginController(), 'authenticate']);
$router->post('/logout', [new LoginController(), 'logout']);

$router->get('/register', [new RegisterController(), 'index']);
$router->post('/register', [new RegisterController(), 'store']);

$router->handleRequest();