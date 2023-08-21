<!DOCTYPE html>
<html>
<head>
    <title><?= page_title() ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= page_description() ?>">
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
    </div>
</div>

<div class="foot wrap">
    Froq! Framework · <a href="/">Home</a> · <a href="/docs">Docs</a> · <a href="//github.com/froq">GitHub</a>
</div>

<link rel="stylesheet" href="/asset/app.css">
<link rel="stylesheet" href="/asset/prism.css">
<script src="/asset/app.js"></script>
<script src="/asset/prism.js"></script>

<script>
froq.ready(() => {
    const getText = el => el.textContent.trim();
    const slugName = name => name.replace(/[^\w]/g, " ").trim()
                                 .replace(/\s+/g, "-").toLowerCase();

    // Scroll to anchor.
    setTimeout(() => {
        if (location.hash) {
            let href = slugName(location.hash);
            let anch = froq.find(`[name="${href}"]`);
            anch && window.scrollTo({top: anch.offsetTop - 5});
        }
    }, 1000);

    // Header anchors.
    froq.findAll(".docs > h2, .docs > h3, .docs > h4").forEach(el => {
        let a = document.createElement("a");
        a.name = slugName(getText(el));
        a.href = "#" + a.name;
        a.textContent = "¶";
        el.appendChild(a);
    });

    // Outer links.
    froq.findAll("a[href]").forEach(el => {
        if (el.hostname !== location.hostname) {
            el.setAttribute("target", "_blank");
        }

        if (getText(el) === 'THE_SOURCE_CODE') {
            el.title = "Source Code on GitHub";
            el.innerHTML = "[<s>GitHub</s>]";
            el.style.visibility = "visible";
        }
    });

    // Prism (add method class).
    froq.findAll(".token.function").forEach(el => {
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

    // Prism (fix class & namespace names).
    froq.findAll(".token.class-name, .token.scope, .token.package").forEach(el => {
        let txt = getText(el);
        let prev, next;

        const replaceNodes = (nodes) => {
            let rep = document.createElement("span");
            rep.className = "token class-name";

            for (let node of nodes) {
                rep = rep.cloneNode();
                rep.textContent = node.textContent;
                node.replaceWith(rep);
            }
        };

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

    // Prism (fix array/null keywords).
    froq.findAll(".token.keyword").forEach(el => {
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

    // Prism (bash comments).
    froq.findAll("code.language-bash").forEach(el => {
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
    froq.find(".main").style.minHeight = froq.find(".side").scrollHeight + "px";
})
</script>

</body>
</html>
