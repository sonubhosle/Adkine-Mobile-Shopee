<script>
    window.mobileShopLocale = <?= json_encode(current_language(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.mobileShopTranslations = <?= json_encode(mobile_shop_translations(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    (function () {
        if (window.mobileShopLocale !== 'mr') return;

        var translations = window.mobileShopTranslations;
        var phrases = Object.keys(translations).filter(function (phrase) {
            return /\s/.test(phrase) || ['Add', 'items', 'Only', 'left', 'Options'].indexOf(phrase) !== -1;
        }).sort(function (a, b) { return b.length - a.length; });
        var translatedTextNodes = new WeakMap();

        function translateValue(value) {
            if (Object.prototype.hasOwnProperty.call(translations, value)) return translations[value];

            return phrases.reduce(function (translated, phrase) {
                var escaped = phrase.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                var endBoundary = /[A-Za-z0-9]$/.test(phrase) ? '(?=$|[^A-Za-z0-9])' : '';
                var expression = new RegExp('(^|[^A-Za-z0-9])(' + escaped + ')' + endBoundary, 'g');
                return translated.replace(expression, function (match, prefix, text) {
                    return prefix + translations[text];
                });
            }, value);
        }

        function translateAttributes(element) {
            ['aria-label', 'aria-description', 'title', 'placeholder', 'alt', 'value'].forEach(function (attribute) {
                if (attribute === 'value' && !(element.tagName === 'BUTTON' || (element.tagName === 'INPUT' && /^(button|submit)$/i.test(element.type)))) return;
                var value = element.getAttribute(attribute);
                if (!value) return;
                var translated = translateValue(value);
                if (translated !== value) element.setAttribute(attribute, translated);
            });
        }

        document.title = translateValue(document.title);
        function translateTextNode(node) {
            if (node.parentElement && /^(SCRIPT|STYLE|NOSCRIPT|TEXTAREA|CODE|PRE)$/.test(node.parentElement.tagName)) return;
            var original = node.nodeValue;
            if (translatedTextNodes.get(node) === original) return;
            var text = original.trim();
            if (!text) {
                translatedTextNodes.set(node, original);
                return;
            }
            var translated = translateValue(text);
            var result = translated === text ? original : original.replace(text, translated);
            translatedTextNodes.set(node, result);
            if (result !== original) node.nodeValue = result;
        }

        function translateNode(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                translateTextNode(node);
                return;
            }

            if (node.nodeType !== Node.ELEMENT_NODE) return;
            if (/^(SCRIPT|STYLE|NOSCRIPT|TEXTAREA|CODE|PRE)$/.test(node.tagName)) return;

            var walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, {
                acceptNode: function (textNode) {
                    var parent = textNode.parentElement;
                    return parent && !/^(SCRIPT|STYLE|NOSCRIPT|TEXTAREA|CODE|PRE)$/.test(parent.tagName)
                        ? NodeFilter.FILTER_ACCEPT
                        : NodeFilter.FILTER_REJECT;
                }
            });
            while (walker.nextNode()) translateTextNode(walker.currentNode);

            var elements = [node].concat(Array.prototype.slice.call(node.querySelectorAll('*')));
            elements.forEach(translateAttributes);
        }

        translateNode(document.body);
        new MutationObserver(function (records) {
            records.forEach(function (record) {
                if (record.type === 'attributes') translateAttributes(record.target);
                else if (record.type === 'characterData') translateTextNode(record.target);
                else record.addedNodes.forEach(translateNode);
            });
        }).observe(document.body, {
            attributes: true,
            attributeFilter: ['aria-label', 'aria-description', 'title', 'placeholder', 'alt', 'value'],
            characterData: true,
            childList: true,
            subtree: true
        });
    })();
</script>
