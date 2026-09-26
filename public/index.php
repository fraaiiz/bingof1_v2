<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Router\Router;
use App\Controller\HomeController;
use App\Controller\MonBingoController;
use App\Controller\LesBingosController;
use App\Controller\InfosController;
use App\Controller\CalendrierController;
use App\Controller\ClassementController;
use App\Controller\LoginController;

$router = new Router();

$router->get('/', [new HomeController(), 'index']);

$router->get('/mon-bingo', [new MonBingoController(), 'index']);
$router->get('/les-bingos', [new LesBingosController(), 'index']);

$router->get('/infos', [new InfosController(), 'redirectToCurrentSeason']);
$router->get('/calendrier', [new CalendrierController(), 'redirectToCurrentSeason']);
$router->get('/classement', [new ClassementController(), 'redirectToCurrentSeason']);

$router->get('/saisons/{annee}/infos', [new InfosController(), 'index']);
$router->get('/saisons/{annee}/calendrier', [new CalendrierController(), 'index']);
$router->get('/saisons/{annee}/classement', [new ClassementController(), 'index']);

$router->get('/login', [new LoginController(), 'index']);

$router->handleRequest();