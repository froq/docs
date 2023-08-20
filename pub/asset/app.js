!function (win) {

var froq = {};

var readyCallbacks = [];
var readyCallbacksFire = function () {
    while (readyCallbacks.length) {
        readyCallbacks.shift()();
    }
};

froq.ready = function (callback) {
    if (callback) {
        readyCallbacks.push(callback);
    }

    win.document.addEventListener('DOMContentLoaded', function _() {
        win.document.removeEventListener('DOMContentLoaded', _, false);
        readyCallbacksFire();
    }, false);
};

froq.find = function (selector, root) {
    return (root || win.document).querySelector(selector);
};
froq.findAll = function (selector, root) {
    return (root || win.document).querySelectorAll(selector);
};

win.froq = froq;
win.log = function () {
    console.log.apply(win.console, arguments);
};

}(this);
