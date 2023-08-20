<?php
namespace app\controller;

use froq\http\response\Status;

class AppController extends \froq\app\Controller
{
    var bool $useView = true;

    /** Init. */
    function init()
    {
        $this->includeFunctions();
    }

    /** Index. */
    function index()
    {}

    /** Error. */
    function error($e = null)
    {
        // @todo: remove?
        ini_set('display_errors', false);

        $code = $e?->getCode() ?: 500;
        $message = 'Internal server error';

        // Ensure valid code.
        $code = ($code >= 100 && $code <= 599) ? $code : 500;

        // Purify error message (eg: 404 => Not Found).
        if ($code >= 400) $message = Status::getTextByCode($code);

        return $this->view('_error', ['code' => $code, 'message' => $message], $code);
    }

    protected function fail($code)
    {
        return $this->error($this->createHttpException($code));
    }

    private function includeFunctions()
    {
        include_once APP_DIR . '/app/library/fun.php';
    }
}
