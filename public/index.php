<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
	$appUrl = getenv('APP_URL');
	$scheme = is_string($appUrl) ? parse_url(trim($appUrl), PHP_URL_SCHEME) : null;

	session_set_cookie_params([
		'lifetime' => 0,
		'path' => '/',
		'secure' => is_string($scheme) && strtolower($scheme) === 'https',
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	session_start();
}

use App\Router\Router;
use App\Controller\HomeController;
use App\Controller\ChatController;
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
use App\Controller\PasswordResetController;
use App\Controller\AdminController;
use App\Controller\AdminSeasonController;
use App\Controller\AdminDriverController;
use App\Controller\AdminTeamController;
use App\Controller\AdminUserController;
use App\Controller\AdminTicketController;
use App\Controller\AdminSettingsController;
use App\Controller\ContactController;
use App\Controller\CguController;
use App\Controller\DevnotesController;
use App\Service\MaintenanceGate;
use App\Service\SessionGuard;

$router = new Router();

$router->get('/', [new HomeController(), 'index']);
$router->get('/chat', [new ChatController(), 'index']);
$router->post('/chat', [new ChatController(), 'store']);
$router->post('/predictions', [new PredictionController(), 'store']);

$router->get('/mon-bingo', [new MonBingoController(), 'index']);
$router->post('/mon-bingo', [new MonBingoController(), 'index']);
$router->get('/les-bingos', [new LesBingosController(), 'index']);

$router->get('/infos', [new InfosController(), 'redirectToDefaultSeason']);
$router->get('/calendrier', [new CalendrierController(), 'redirectToDefaultSeason']);
$router->get('/classement', [new ClassementController(), 'redirectToDefaultSeason']);

$router->get('/saisons/{annee}/infos', [new InfosController(), 'index']);
$router->get('/saisons/{annee}/calendrier', [new CalendrierController(), 'index']);
$router->get('/saisons/{annee}/classement', [new ClassementController(), 'index']);
$router->get('/saisons/{annee}/courses/{id}/resultats', [new ResultatController(), 'index']);
$router->get('/saisons/{annee}/courses/{id}/resultats/edition', [new EditResultatController(), 'index']);
$router->post('/saisons/{annee}/courses/{id}/resultats/edition', [new EditResultatController(), 'store']);

$router->get('/contact', [new ContactController(), 'index']);
$router->post('/contact', [new ContactController(), 'store']);
$router->get('/cgu', [new CguController(), 'index']);
$router->get('/devnotes', [new DevnotesController(), 'index']);

$router->get('/login', [new LoginController(), 'index']);
$router->post('/login', [new LoginController(), 'authenticate']);
$router->post('/logout', [new LoginController(), 'logout']);
$router->get('/mot-de-passe-oublie', [new PasswordResetController(), 'requestForm']);
$router->post('/mot-de-passe-oublie', [new PasswordResetController(), 'send']);
$router->get('/mot-de-passe-oublie/{token}', [new PasswordResetController(), 'chooseForm']);
$router->post('/mot-de-passe-oublie/{token}', [new PasswordResetController(), 'update']);

$router->get('/admin', [new AdminController(), 'index']);
$router->get('/admin/saisons', [new AdminSeasonController(), 'index']);
$router->post('/admin/saisons', [new AdminSeasonController(), 'prepare']);
$router->post('/admin/saisons/confirmation', [new AdminSeasonController(), 'confirm']);
$router->get('/admin/saisons/{id}', [new AdminSeasonController(), 'show']);
$router->get('/admin/saisons/{id}/courses/{courseId}', [new AdminSeasonController(), 'edit']);
$router->post('/admin/saisons/{id}/courses', [new AdminSeasonController(), 'prepareCourse']);
$router->post('/admin/saisons/{id}/courses/{courseId}', [new AdminSeasonController(), 'prepareCourseUpdate']);
$router->post('/admin/saisons/{id}/courses/{courseId}/suppression', [new AdminSeasonController(), 'prepareCourseDelete']);

$router->get('/admin/pilotes', [new AdminDriverController(), 'index']);
$router->post('/admin/pilotes', [new AdminDriverController(), 'prepareCreate']);
$router->post('/admin/pilotes/confirmation', [new AdminDriverController(), 'confirm']);
$router->get('/admin/pilotes/{id}', [new AdminDriverController(), 'show']);
$router->post('/admin/pilotes/{id}', [new AdminDriverController(), 'prepareUpdate']);
$router->post('/admin/pilotes/{id}/suppression', [new AdminDriverController(), 'prepareDelete']);
$router->post('/admin/pilotes/{id}/engagements', [new AdminDriverController(), 'prepareEngagement']);
$router->get('/admin/pilotes/{id}/engagements/{engagementId}', [new AdminDriverController(), 'editEngagement']);
$router->post('/admin/pilotes/{id}/engagements/{engagementId}', [new AdminDriverController(), 'prepareEngagementUpdate']);
$router->post('/admin/pilotes/{id}/engagements/{engagementId}/suppression', [new AdminDriverController(), 'prepareEngagementDelete']);

$router->get('/admin/ecuries', [new AdminTeamController(), 'index']);
$router->post('/admin/ecuries', [new AdminTeamController(), 'prepareCreate']);
$router->post('/admin/ecuries/confirmation', [new AdminTeamController(), 'confirm']);
$router->get('/admin/ecuries/{id}', [new AdminTeamController(), 'show']);
$router->post('/admin/ecuries/{id}', [new AdminTeamController(), 'prepareUpdate']);
$router->post('/admin/ecuries/{id}/suppression', [new AdminTeamController(), 'prepareDelete']);
$router->post('/admin/ecuries/{id}/saisons', [new AdminTeamController(), 'prepareEntry']);
$router->get('/admin/ecuries/{id}/saisons/{entryId}', [new AdminTeamController(), 'editEntry']);
$router->post('/admin/ecuries/{id}/saisons/{entryId}', [new AdminTeamController(), 'prepareEntryUpdate']);
$router->post('/admin/ecuries/{id}/saisons/{entryId}/suppression', [new AdminTeamController(), 'prepareEntryDelete']);

$router->get('/admin/users', [new AdminUserController(), 'index']);
$router->post('/admin/users/confirmation', [new AdminUserController(), 'confirm']);
$router->get('/admin/users/{id}', [new AdminUserController(), 'show']);
$router->post('/admin/users/{id}', [new AdminUserController(), 'prepareUpdate']);
$router->post('/admin/users/{id}/suppression', [new AdminUserController(), 'prepareDelete']);

$router->get('/admin/settings', [new AdminSettingsController(), 'index']);
$router->post('/admin/settings', [new AdminSettingsController(), 'update']);

$router->get('/admin/tickets', [new AdminTicketController(), 'index']);
$router->get('/admin/tickets/{id}', [new AdminTicketController(), 'show']);
$router->post('/admin/tickets/{id}/prise-en-charge', [new AdminTicketController(), 'takeCharge']);
$router->post('/admin/tickets/{id}/reponse', [new AdminTicketController(), 'reply']);

$router->get('/register', [new RegisterController(), 'index']);
$router->post('/register', [new RegisterController(), 'store']);

SessionGuard::enforce();
MaintenanceGate::enforce();
$router->handleRequest();