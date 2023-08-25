<?php
namespace app\controller;

use froq\http\response\Status;
use Throwable;

class AppController extends \froq\app\Controller {
    public bool $useView = true;

    /** Init. */
    public function init(): void {
        $this->includeFunctions();
    }

    /** Index. */
    public function index(): string {
        return ''; // No-op.
    }

    /** Error. */
    public function error(Throwable $e = null): string {
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

    protected function fail(int $code): string {
        return $this->error($this->createHttpException($code));
    }

    private function includeFunctions(): void {
        include_once APP_DIR . '/app/library/fun.php';
    }
}
