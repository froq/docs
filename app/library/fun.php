<?php
// Default page stuff.
const PAGE_TITLE = 'Froq! Framework';
const PAGE_DESCRIPTION = 'Froq! Hassle-free PHP framework.';

function page_title(): string {
    $view = app()::registry()::get('@view');
    $title = $view->getData('title');

    return join(' | ', filter([PAGE_TITLE, $title]));
}

function page_description(): string {
    return PAGE_DESCRIPTION; // For now?
}

function test_path(string ...$patterns): bool {
    $path = app()->request->getPath();

    foreach ($patterns as $pattern) {
        if (preg_test("~{$pattern}~", $path)) {
            return true;
        }
    }

    return false;
}

function is_doc_path(): bool {
    return test_path('^/$', '^/docs/?.*');
}

function is_api_path(): bool {
    return test_path('^/api(/?.*)?');
}
