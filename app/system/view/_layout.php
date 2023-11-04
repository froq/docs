<!DOCTYPE html>
<html>
<head>
    <title><?= page_title() ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= page_description() ?>">

    <link href="/asset/app.css" rel="preload">
    <link href="/asset/prism.css" rel="preload">
</head>
<body>

<div class="head wrap">
    <div class="logo">Froq! Framework</div>
    <div class="navi">
        <a href="/">Home</a>
        <a href="/docs">Docs</a>
        <a href="//github.com/froq">GitHub</a>
    </div>
</div>

<div class="body wrap">
    <div class="main">
        <div><?= $CONTENT ?></div>
    </div><!-- .main -->

    <div class="side">
        <b>Getting Started</b>
        <a href="/docs#installation">Installation</a>
        <a href="/docs#configuration">Configuration</a>
        <a href="/docs#web-servers">Web Servers</a>
        <br>
        <b>Application & Components</b>
        <a href="/docs/app">Application</a>
        <a href="/docs/app-controller">Controller</a>
        <a href="/docs/app-repository">Repository</a>
        <a href="/docs/app-view">View</a>
        <a href="/docs/app-routing">Routing</a>
        <br>
        <b>HTTP & Components</b>
        <a href="/docs/http-request">Request</a>
        <a href="/docs/http-response">Response</a>
        <a href="/docs/http-payloads">Payloads</a>
        <br>
        <b>Database & Components</b>
        <a href="/docs/database">Database</a>
        <a href="/docs/database-queries-results">Queries & Results</a>
        <a href="/docs/database-transactions">Transactions</a>
        <a href="/docs/database-entities">Entities</a>
        <br>
        <b>Utilities</b>
        <a href="/docs/util-sugars">Sugars</a>
        <a href="/docs/util-sugar-classes">Sugar Classes</a>
        <a href="/docs/util-sugar-functions">Sugar Functions</a>
        <a href="/docs/util-sugar-constants">Sugar Constants</a>
    </div>
</div>

<div class="foot wrap">
    Froq! Framework · <a href="/">Home</a> · <a href="/docs">Docs</a> · <a href="//github.com/froq">GitHub</a>
</div>

<link href="/asset/app.css" rel="stylesheet">
<link href="/asset/prism.css" rel="stylesheet">
<script src="/asset/app.js"></script>
<script src="/asset/prism.js"></script>

<script>
app.ready(() => {
    const getText = el => el.textContent.trim();
    const slugName = name => name.replace(/[^\w]/g, " ").trim()
                                 .replace(/\s+/g, "-").toLowerCase();

    // Scroll to anchor.
    setTimeout(() => {
        if (location.hash) {
            let href = slugName(location.hash);
            let anch = app.find(`[name="${href}"]`);
            anch && window.scrollTo({top: anch.offsetTop - 5});
        }
    }, 1000);

    // Header anchors.
    app.findAll(".docs > h2, .docs > h3, .docs > h4").forEach(el => {
        let a = document.createElement("a");
        a.name = slugName(getText(el));
        a.href = "#" + a.name;
        a.textContent = "¶";
        el.appendChild(a);
    });

    // Links.
    app.findAll("a[href]").forEach(el => {
        // External links.
        if (el.hostname !== location.hostname) {
            el.setAttribute("target", "_blank");
        }

        // GitHub source links.
        if (getText(el) === '#git') {
            el.title = "Source Code on GitHub";
            el.innerHTML = "[<s>GitHub</s>]";
            el.style.visibility = "visible";
        }
    });

    // Prism (add method class).
    app.findAll(".token.function").forEach(el => {
        // fn() => ..
        if (el.textContent === "fn" && el.nextSibling.textContent === "(") {
            el.classList.replace("function", "keyword");
            return;
        }

        if (el.previousElementSibling.tagName.toLowerCase() === "span") {
            let txt = getText(el.previousElementSibling);
            switch (true) {
                case txt === ">":
                case txt === "function":
                    // Not "=> foo(..)"
                    let pprev = el.previousElementSibling.previousElementSibling;
                    if (pprev && getText(pprev) === "=") {
                        return;
                    }
                    el.classList.add("method");
                    break;
                // Foo::bar()
                case el.previousElementSibling.classList.contains("scope")
                  && el.previousElementSibling.textContent.includes("::"):
                    el.classList.replace("function", "method");
            }
        }
    });

    // const reBinTypes = /^\??(int|float|string|bool|array|object|callable|iterable|mixed)\s*$/
    const reClassName = /^[\?\s]?[A-Z][a-z]*/
    const replaceNodes = (nodes) => {
        let rep = document.createElement("span");
        rep.className = "token class-name";

        for (let node of nodes) {
            rep = rep.cloneNode();
            rep.textContent = node.textContent;
            node.replaceWith(rep);
        }
    };

    // Prism (fix class & namespace names).
    app.findAll(".token.class-name, .token.scope, .token.package").forEach(el => {
        let txt = getText(el);
        let prev, next;

        // acme\Foo, acme\Foo::BAR
        if (txt.includes("\\")) {
            el.classList.add("package", "package-class-name");

            prev = el.previousElementSibling;
            next = el.nextSibling;

            if (getText(prev) === "namespace") {
                return;
            }

            let tmp = txt.split(/\\/);
            let end = tmp[tmp.length - 1];
            let cls, rex; tmp = null;

            // foo\bar\{Foo, ..}
            if (end === "" && next.textContent === "{") {
                let nodes = [];

                while (next && next.textContent !== "}") {
                    // Plain text (others are span with "punctation" class).
                    if (next.nodeType === Node.TEXT_NODE) {
                        nodes.push(next);
                    }
                    next = next.nextSibling;
                }

                replaceNodes(nodes);

                return; // Done!!!
            } else {
                if (end.includes("::")) {
                    cls = rex = end.slice(0, -2);
                } else {
                    cls = end;
                    rex = end + "\\s*$";
                }
            }

            el.innerHTML = el.innerHTML.replace(
                new RegExp(rex),
                `<span class="token class-name">${cls}</span>`
            );
        }
        // use Foo, Bar;
        else if (el.classList.contains("package")) {
            // First class (Foo).
            el.classList.replace("package", "class-name");

            // Next texts (if any).
            next = el.nextSibling;
            if (next.textContent === ",") {
                let nodes = [];

                while (next && next.textContent !== ";") {
                    // Plain text (others are span with "punctation" class).
                    if (next.nodeType === Node.TEXT_NODE) {
                        nodes.push(next);
                    }
                    next = next.nextSibling;
                }

                replaceNodes(nodes);
            }
        }
        // Foo::BAR
        else if (el.classList.contains("scope")) {
            el.classList.add("class-name");
        }
    });

    // Prism (add arg type).
    app.findAll(".token.function").forEach(el => {
        let nodes = [], next = el.nextSibling;

        if (next.textContent === "(") {
            let nodes = [];

            while (next && next.textContent !== ")") {
                if (
                    // Plain text (others are span).
                    next.nodeType === Node.TEXT_NODE
                    // Only classes (for now?).
                    && reClassName.test(next.textContent)
                ) {
                    nodes.push(next);
                }
                next = next.nextSibling;
            }

            replaceNodes(nodes);
        }
    });


    // Prism (fix array/null keywords).
    app.findAll(".token.keyword").forEach(el => {
        let txt = getText(el);
        switch (true) {
            case (txt === "array"):
                el.replaceWith("array");
                break;
            case (txt === "null"):
                el.classList.replace("keyword", "constant");
                break;
            case (txt === "default"):
                // foo(default: null)
                if (el.previousElementSibling.textContent === ","
                    && el.nextElementSibling.textContent === ":") {
                    el.replaceWith("default");
                }
                break;
        }
    });

    // Prism (remove # comments).
    app.findAll(".language-php > .comment").forEach(el => {
        if (el.textContent.startsWith("#")) {
            el.classList.replace("comment", "comment--");
        }
    });

    // Prism (bash comments).
    app.findAll("code.language-bash").forEach(el => {
        if (el.textContent.includes("# ")) {
            let lines = [];

            getText(el).split(/\r?\n|\n|\r/).forEach(line => {
                if (line.slice(0, 2) === "# ") {
                    line = `<span class="token comment">${line}</span>`;
                }
                lines.push(line);
            });

            el.innerHTML = lines.join("\n");
        }
    });

    // Main container height.
    // app.find(".main").style.minHeight = app.find(".side").scrollHeight + "px";
})
</script>

</body>
</html>
