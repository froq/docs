<?php
namespace app\controller;

class HomeController extends AppController {
    function index(): string {
        return $this->view('home');
    }
}
