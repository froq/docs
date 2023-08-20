<?php
namespace app\controller;

use froq\file\File;

class DocsController extends AppController
{
    const TITLE = 'Docs';

    function index($x = null)
    {
        if ($x === null) {
            [, $content] = $this->getDoc('_index');

            return $this->view('docs', [
                'title'   => self::TITLE,
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
            ->replace(['-'], ['_']) // Function "_" is internal.
            ->slice(0, 50)          // Enough for a file name.
            ->slug(preserve: '_')
        ;

        $file = new File(format('./app/system/docs/%s.md', $name));

        if ($file->exists()) {
            $file->open();

            $title = self::TITLE;
            $upath = chop($this->request->getPath(), '/');

            if (!hash_equals($upath, '/docs')) {
                // Add extracting from first line (eg: # Application ..).
                $title .= ' | ' . trim(grep('~# ([^\[]+)~', (string) $file->readLine()));
            }

            $content = $file->readAll();
            $content = (new \Parsedown)->text($content);

            return [$title, $content, true];
        }

        // Not found.
        return [null, null, false];
    }
}
