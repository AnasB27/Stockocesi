<?php
if (!class_exists('Twig\\Environment')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

class TwigConfig {
    private static $instance = null;
    private $twig;

    private function __construct() {
        try {
            $loader = new \Twig\Loader\FilesystemLoader(ROOT_PATH . '/templates');
            
            $this->twig = new \Twig\Environment($loader, [
                'cache' => false,
                'debug' => true,
                'auto_reload' => true,
            ]);

            $this->twig->addExtension(new \Twig\Extension\DebugExtension());

            if (session_status() === PHP_SESSION_ACTIVE) {
                $this->twig->addGlobal('session', $_SESSION);
            }
            
            $this->twig->addGlobal('app', [
                'request' => [
                    'get' => $_GET
                ]
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'initialisation de Twig: ' . $e->getMessage());
            throw $e;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getTwig() {
        return $this->twig;
    }
}
