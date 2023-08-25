<?php
namespace app\controller;

/**
 * Home Controller.
 * Route: /
 */
class HomeController extends AppController {
    /** @override */
    function index(): string {
        return $this->view('home');
    }
}
