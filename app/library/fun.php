<?php
// Default page stuff.
const PAGE_TITLE = 'Froq! Framework';
const PAGE_DESCRIPTION = 'Froq! Hassle-free PHP framework.';

function page_title() {
    $view  = app()::registry()::get('@view');
    $title = $view->getData('title');

    return join(' | ', filter([PAGE_TITLE, $title]));
}

function page_description() {
    return PAGE_DESCRIPTION; // @fornow
}
