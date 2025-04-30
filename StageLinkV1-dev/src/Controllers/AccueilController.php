<?php
require_once ROOT_PATH . '/src/controllers/controller.php';

class accueilcontroller extends controller {
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        echo $this->render('accueil', [
            'pageTitle' => 'Accueil - StageLink'
        ]);
    }
}



