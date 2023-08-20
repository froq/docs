<?php
// Default page stuff.
const PAGE_TITLE = 'Froq! Framework';
const PAGE_DESCRIPTION = 'Froq! Hassle-free PHP framework.';

function page_title() {
    $view = app()::registry()::get('@view');
    $title = $view->getData('title');

    return join(' | ', filter([PAGE_TITLE, $title]));
}

function page_description() {
    // @fornow
    return PAGE_DESCRIPTION;
}

// function froq_data($key) {
//     $view = app()::registry()::get('@view');

//     return $view->getData($key);
// }

// function froq_layout($name) {
//     $file = sprintf('%s/app/system/view/_layout_%s.php', APP_DIR, $name);

//     ob_start();
//     include $file;
//     return ob_get_clean() . PHP_EOL;
// }
