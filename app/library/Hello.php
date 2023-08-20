<?php
namespace app\library;

class Hello {
    function __construct(public $name) {}

    function greet() { return format('Hello, %s', ($this->name)); }
}
