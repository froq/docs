<?php
namespace app\controller;

/**
 * Home Controller.
 * Route: /
 */
class HomeController extends AppController {
    /** @override */
    public function index(): string {
        return $this->view('home');
    }
}
