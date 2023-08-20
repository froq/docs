<?php
namespace app\controller;

class HomeController extends AppController
{
    function index()
    {
        return $this->view('home');
    }
}
