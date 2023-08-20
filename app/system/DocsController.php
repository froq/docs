<?php
namespace app\controller;

use froq\http\response\Status;
use froq\http\response\payload\Payload;
use froq\file\FileSystem;

class DocsController extends AppController
{
    function index($x = null)
    {
        if ($x === null) {
            [, $content] = $this->getDoc('_index');

            return $this->view('docs', [
                'title'   => 'Documentation',
                'content' => $content
            ]);
        }

        [$title, $content, $ok] = $this->getDoc($x);

        if ($ok) {
            return $this->view('docs', [
                'title'   => $title,
                'content' => $content
            ]);
        }

        return $this->fail(404);
    }

    private function getDoc($x)
    {
        $name = xstring($x)
            // Function "_" is internal.
            ->replace(['-'], ['_'])
            ->sub(0, 255) // Drop excessive parts.
            ->lower()     // Lowerify name.
            ->slug('_')   // Slugify name.
        ;

        $file = format('./app/system/docs/%s.md', $name);

        if (file_exists($file)) {
            $title = null; // @todo

            $content = file_read($file);
            $content = (new \Parsedown)->text($content);

            return [$title, $content, true];
        }

        // Not found.
        return [null, null, false];
    }
}
