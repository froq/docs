<?php
namespace app\controller;

use froq\file\File;
use Parsedown;

/**
 * Docs Controller.
 * Route: /docs, /docs/:id
 */
class DocsController extends AppController {
    private const TITLE = 'Docs';

    /** @override */
    public function index(string $id = null): string {
        if (!$id) {
            [, $content] = $this->getDoc('_index');

            return $this->view('docs', [
                'title'   => self::TITLE,
                'content' => $content
            ]);
        }

        [$title, $content, $ok] = $this->getDoc($id);

        if ($ok) {
            return $this->view('docs', [
                'title'   => $title,
                'content' => $content
            ]);
        }

        return $this->fail(404);
    }

    /**
     * Get a doc contents after parsing.
     */
    private function getDoc(string $id): array {
        $name = xstring($id)
            ->replace(['-'], ['_']) // Function "_" is internal.
            ->slice(0, 50)          // Enough for a file name.
            ->slug(preserve: '_')
        ;

        $file = new File(format('./app/system/docs/%s.md', $name));

        if ($file->exists()) {
            $file->open();

            $title = self::TITLE;
            $upath = chop($this->request->getPath(), '/');

            // Skip index page.
            if (!hash_equals($upath, '/docs')) {
                // Append title extracting it from first line (eg: # Application ..).
                $title .= ' | ' . trim(grep('~# +([^\[]+)~', (string) $file->readLine()));
            }

            $content = $file->readAll();
            $content = (new Parsedown)->text($content);

            return [$title, $content, true];
        }

        // Not found.
        return [null, null, false];
    }
}
