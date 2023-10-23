!function (win) {

const app = {};

const readyCallbacks = [];
const readyCallbacksFire = function () {
    while (readyCallbacks.length) {
        readyCallbacks.shift()();
    }
};

app.ready = function (callback) {
    if (callback) {
        readyCallbacks.push(callback);
    }

    win.document.addEventListener('DOMContentLoaded', function _() {
        win.document.removeEventListener('DOMContentLoaded', _, false);
        readyCallbacksFire();
    }, false);
};

app.find = function (selector, root) {
    return (root || win.document).querySelector(selector);
};
app.findAll = function (selector, root) {
    return (root || win.document).querySelectorAll(selector);
};

win.app = app;
win.log = function () {
    console.log.apply(win.console, arguments);
};

}(this);
