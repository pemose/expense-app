<?php

require_once 'classes/userSessionInstance.php';
/**
 * Controlador que también maneja las sesiones
 */
class SessionController extends Controller{
    
    private $userSession;
    private $username;
    private $userid;

    private $session;
    private $sites;
    private $defaultSites;

    private $user;
 
    function __construct(){
        parent::__construct();

        $this->init();

 /*     $this->userSession = new UserSessionInstance();
        $this->username    = $this->userSession->getUserSessionData()['username'];
        $this->userid      = $this->userSession->getUserSessionData()['id']; */
    }

    public function getUserSession(){
        return $this->userSession;
    }

    public function getUsername(){
        return $this->username;
    }

    public function getUserId(){
        return $this->userid;
    }

    /**
     * Inicializa el parser para leer el .json
     */
    private function init(){
        //se crea nueva sesión
        $this->session = new Session();
        //se carga el archivo json con la configuración de acceso
        $json = $this->getJSONFileConfig();
        // se asignan los sitios
        $this->sites = $json['sites'];
        // se asignan los sitios por default, los que cualquier rol tiene acceso
        $this->defaultSites = $json['default-sites'];
        // inicia el flujo de validación para determinar
        // el tipo de rol y permismos
        //$this->validateSession();
    }
    /**
     * Abre el archivo JSON y regresa el resultado decodificado
     */
    private function getJSONFileConfig(){
        $string = file_get_contents("config/access.json");
        $json = json_decode($string, true);

        return $json;
    }

    /**
     * Implementa el flujo de autorización
     * para entrar a las páginas
     */
    function validateSession(){
        //Si existe la sesión
        if($this->existsSession()){
            $role = $this->getUserSessionData()->getRole();
            error_log("sessionController::validateSession(): username:" . $this->user->getUsername() . " - role: " . $this->user->getRole());
            if($this->isPublic()){
                $this->redirectDefaultSiteByRole($role);
            }else{
                if($this->isAuthorized($role)){
                    //no pasa nada, deja pasar
                }else{
                    //
                    $this->redirectDefaultSiteByRole($role);
                }
            }
        }else{
            //No existe ninguna sesión
            //se valida si el acceso es público o no
            if($this->isPublic()){
                //la pagina es publica
                //no pasa nada
            }else{
                //la página no es pública
                //redirect al login
                header('location: '. constant('URL') . '');
            }
        }
    }
    /**
     * Valida si existe sesión, 
     * si es verdadero regresa el usuario actual
     */
    function existsSession(){
        if(!$this->session->exists()) return false;
        if($this->session->getCurrentUser() == NULL) return false;

        $userid = $this->session->getCurrentUser();

        if($userid) return true;

        return false;
    }

    function getUserSessionData(){
        $id = $this->session->getCurrentUser();
        $this->user = new User();
        $this->user->get($id);
        error_log("sessionController::getUserSessionData(): " . $this->user->getUsername());
        return $this->user;
    }

    public function initialize($user){
        error_log("sessionController::initialize(): user: " . $user->getUsername());
        $this->session->setCurrentUser($user->getId());
        $this->authorizeAccess($user->getRole());
    }

    private function isPublic(){
        $currentURL = $this->getCurrentPage();
        for($i = 0; $i < sizeof($this->sites); $i++){
            if($currentURL === $this->sites[$i]['site'] && $this->sites[$i]['access'] === 'public'){
                return true;
            }
        }
        return false;
    }

    private function redirectDefaultSiteByRole($role){
        $url = '';
        for($i = 0; $i < sizeof($this->sites); $i++){
            if($this->sites[$i]['role'] === $role){
                $url = '/expense-app/'.$this->sites[$i]['site'];
            break;
            }
        }
        header('location: '.$url);
        
    }

    private function isAuthorized($role){
        $currentURL = $this->getCurrentPage();
        for($i = 0; $i < sizeof($this->sites); $i++){
            if($currentURL === $this->sites[$i]['site'] && $this->sites[$i]['role'] === $role){
                return true;
            }
        }
        return false;
    }

    private function getCurrentPage(){
        $actual_link = trim("$_SERVER[REQUEST_URI]");
        $url = explode('/', $actual_link);
        return $url[2];
    }

    function authorizeAccess($role){
        error_log("sessionController::authorizeAccess(): role: $role");
        switch($role){
            case 'user':
                header('location: '. constant('URL').'dashboard');
            break;
            case 'admin':
                header('location: '. constant('URL').'admin');
            break;
            default:
        }
    }
}


?>