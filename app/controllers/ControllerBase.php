<?php

namespace App\Controllers;

use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
    public function initialize()
    {
        $this->view->auth = $this->session->get('auth');
    }

    public function beforeExecuteRoute(Dispatcher $dispatcher)
    {
        $controllerName = $dispatcher->getControllerName();

        // Only check permissions on private controllers
        if ($this->isPrivate($controllerName)) {
            // Get the current identity
            $auth = $this->session->get('auth');

            // If there is no identity available the user is redirected to user/login
            if (!is_array($auth)) {
                //$this->flash->notice('You don\'t have access to this module: private');
                //$dispatcher->forward(['controller' => 'user', 'action' => 'login']);
                $this->response->redirect("/user/login");
                return false;
            }
        }

        return true;
    }

    private function isPrivate($controllerName)
    {
        $privateControllers = array(
            'index',
        );

        return in_array($controllerName, $privateControllers);
    }

    protected function startDownload($filename)
    {
        if (file_exists($filename)) {
            $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_NO_RENDER);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header('Content-Description: File Transfer');
            header('Content-Type: application/txt');
            header('Content-Length: ' . filesize($filename));
            header('Content-Disposition: attachment; filename="'.basename($filename).'"');
            readfile($filename);
            die();
        }
    }
}
