<?php

namespace App\Controllers;

use App\Models\StoreModel;

class StoreController extends Controller {
    private $storeModel;

    public function __construct() {
        parent::__construct();
        $this->storeModel = new StoreModel();
    }

    public function showStorePage() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login');
            exit;
        }

        $userStore = $this->storeModel->getStoreById($_SESSION['store_id']);
        
        echo $this->render('store/store', [
            'pageTitle' => 'Mon magasin - Stock O\' CESI',
            'current_page' => 'store',
            'store' => $userStore
        ]);
    }
}