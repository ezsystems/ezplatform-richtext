(function(global, doc, eZ, CKEDITOR, AlloyEditor) {
    const TABLE_TAG_NAME = 'table';
    const SVG_TAG_NAME = 'svg';
    const HTML_NODE = 1;
    const TEXT_NODE = 3;
    const notInitializeElements = ['strong', 'em', 'u', 'sup', 'sub', 's'];

    class BaseRichText {
        constructor() {
            this.ezNamespace = 'http://ez.no/namespaces/ezpublish5/xhtml5/edit';
            this.xhtmlNamespace = 'http://www.w3.org/1999/xhtml';
            this.customTags = Object.keys(eZ.richText.customTags).filter((key) => !eZ.richText.customTags[key].isInline);
            this.inlineCustomTags = Object.keys(eZ.richText.customTags).filter((key) => eZ.richText.customTags[key].isInline);
            this.attributes = global.eZ.richText.alloyEditor.attributes;
            this.classes = global.eZ.richText.alloyEditor.classes;
            this.customTagsToolbars = this.customTags.map((customTag) => {
                const alloyEditorConfig = eZ.richText.customTags[customTag];

                return new eZ.ezAlloyEditor.ezCustomTagConfig({
                    name: customTag,
                    alloyEditor: alloyEditorConfig,
                });
            });
            this.inlineCustomTagsToolbars = this.inlineCustomTags.map((customTag) => {
                const alloyEditorConfig = eZ.richText.customTags[customTag];

                return new eZ.ezAlloyEditor.ezInlineCustomTagConfig({
                    name: customTag,
                    alloyEditor: alloyEditorConfig,
                });
            });
            this.customStylesConfigurations = Object.entries(eZ.richText.customStyles).map(([customStyleName, customStyleConfig]) => {
                return {
                    name: customStyleConfig.label,
                    style: {
                        element: customStyleConfig.inline ? 'span' : 'div',
                        attributes: {
                            'data-ezelement': customStyleConfig.inline ? 'eztemplateinline' : 'eztemplate',
                            'data-eztype': 'style',
                            'data-ezname': customStyleName,
                        },
                    },
                };
            });
            this.alloyEditorExtraPlugins = eZ.richText.alloyEditor.extraPlugins;
            this.customStyleSelections = global.eZ.ezAlloyEditor.customSelections
                ? Object.values(global.eZ.ezAlloyEditor.customSelections)
                : [];

            this.xhtmlify = this.xhtmlify.bind(this);
        }

        getHTMLDocumentFragment(data) {
            const fragment = doc.createDocumentFragment();
            const root = fragment.ownerDocument.createElement('div');
            const parsedHTML = new DOMParser().parseFromString(data, 'text/xml');
            const importChildNodes = (parent, element, skipElement) => {
                let i;
                let newElement;

                if (skipElement) {
                    newElement = parent;
                } else {
                    if (element.nodeType === Node.ELEMENT_NODE) {
                        newElement = parent.ownerDocument.createElement(element.localName);

                        for (i = 0; i !== element.attributes.length; i++) {
                            importChildNodes(newElement, element.attributes[i], false);
                        }

                        if (element.localName === 'a' && parent.dataset.ezelement === 'ezembed') {
                            element.setAttribute('data-cke-survive', '1');
                        }

                        parent.appendChild(newElement);
                    } else if (element.nodeType === Node.TEXT_NODE) {
                        parent.appendChild(parent.ownerDocument.createTextNode(element.nodeValue));

                        return;
                    } else if (element.nodeType === Node.ATTRIBUTE_NODE) {
                        parent.setAttribute(element.localName, element.value);

                        return;
                    } else {
                        return;
                    }
                }

                for (i = 0; i !== element.childNodes.length; i++) {
                    importChildNodes(newElement, element.childNodes[i], false);
                }
            };

            if (!parsedHTML || !parsedHTML.documentElement || parsedHTML.querySelector('parsererror')) {
                console.warn('Unable to parse the content of the RichText field');

                return fragment;
            }

            fragment.appendChild(root);

            importChildNodes(root, parsedHTML.documentElement, true);
            return fragment;
        }

        emptyEmbed(embedNode) {
            let element = embedNode.firstChild;
            let next;
            let removeClass = () => {};

            while (element) {
                next = element.nextSibling;
                if (!element.getAttribute || !element.getAttribute('data-ezelement')) {
                    embedNode.removeChild(element);
                }
                element = next;
            }

            embedNode.classList.forEach((cl) => {
                let prevRemoveClass = removeClass;

                if (cl.indexOf('is-embed-') === 0) {
                    removeClass = () => {
                        embedNode.classList.remove(cl);
                        prevRemoveClass();
                    };
                }
            });
            removeClass();
        }

        xhtmlify(data) {
            const xmlDocument = doc.implementation.createDocument(this.xhtmlNamespace, 'html', null);
            const htmlDoc = doc.implementation.createHTMLDocument('');
            const section = htmlDoc.createElement('section');
            let body = htmlDoc.createElement('body');

            section.innerHTML = data;
            body.appendChild(section);
            body = xmlDocument.importNode(body, true);
            xmlDocument.documentElement.appendChild(body);

            return body.innerHTML;
        }

        clearCustomTag(customTag) {
            const attributesNodes = customTag.querySelectorAll('[data-ezelement="ezattributes"]');
            const headers = customTag.querySelectorAll('.ez-custom-tag__header');

            attributesNodes.forEach((attributesNode) => attributesNode.remove());
            headers.forEach((header) => header.remove());
        }

        clearAnchor(element) {
            const icon = element.querySelector('.ez-icon--anchor');
            const elementPreviousSibling = element.previousSibling;
            const isTableWithAnchor =
                element.tagName.toLowerCase() === TABLE_TAG_NAME &&
                elementPreviousSibling?.tagName.toLowerCase() === SVG_TAG_NAME;

            if (isTableWithAnchor) {
                elementPreviousSibling.remove();
            } else if (icon) {
                icon.remove();
            } else {
                element.classList.remove('ez-has-anchor');
            }
        }

        appendAnchorIcon(element) {
            const container = doc.createElement('div');
            const icon = `
                <svg class="ez-icon ez-icon--small ez-icon--secondary ez-icon--anchor">
                    <use xlink:href=${window.eZ.helpers.icon.getIconPath('link-anchor')}></use>
                </svg>`;

            container.insertAdjacentHTML('afterbegin', icon);

            const svg = new CKEDITOR.dom.element(container.querySelector('svg'));
            const ckeditorElement = new CKEDITOR.dom.element(element);

            ckeditorElement.append(svg, true);
        }

        clearInlineCustomTag(inlineCustomTag) {
            const icons = inlineCustomTag.querySelectorAll('.ez-custom-tag__icon-wrapper');

            icons.forEach((icon) => icon.remove());
        }

        init(container) {
            const toolbarProps = { attributes: this.attributes, classes: this.classes };
            const customSelections = this.customStyleSelections.map((Selection) => {
                return new Selection(toolbarProps);
            });
            const alloyEditor = AlloyEditor.editable(container.getAttribute('id'), {
                toolbars: {
                    ezadd: {
                        buttons: eZ.richText.alloyEditor.toolbars.ezadd.buttons,
                        tabIndex: 2,
                    },
                    styles: {
                        selections: [
                            ...this.customTagsToolbars,
                            new eZ.ezAlloyEditor.ezLinkConfig(toolbarProps),
                            new eZ.ezAlloyEditor.ezTextConfig({
                                customStyles: this.customStylesConfigurations,
                                inlineCustomTags: this.inlineCustomTags,
                                ...toolbarProps,
                            }),
                            ...this.inlineCustomTagsToolbars,
                            new eZ.ezAlloyEditor.ezParagraphConfig({
                                customStyles: this.customStylesConfigurations,
                                ...toolbarProps,
                            }),
                            new eZ.ezAlloyEditor.ezFormattedConfig({
                                customStyles: this.customStylesConfigurations,
                                ...toolbarProps,
                            }),
                            new eZ.ezAlloyEditor.ezCustomStyleConfig({
                                customStyles: this.customStylesConfigurations,
                                ...toolbarProps,
                            }),
                            new eZ.ezAlloyEditor.ezHeadingConfig({ customStyles: this.customStylesConfigurations, ...toolbarProps }),
                            new eZ.ezAlloyEditor.ezListOrderedConfig({
                                customStyles: this.customStylesConfigurations,
                                ...toolbarProps,
                            }),
                            new eZ.ezAlloyEditor.ezListUnorderedConfig({
                                customStyles: this.customStylesConfigurations,
                                ...toolbarProps,
                            }),
                            new eZ.ezAlloyEditor.ezListItemConfig({
                                customStyles: this.customStylesConfigurations,
                                ...toolbarProps,
                            }),
                            new eZ.ezAlloyEditor.ezEmbedInlineConfig(toolbarProps),
                            new eZ.ezAlloyEditor.ezTableConfig(toolbarProps),
                            new eZ.ezAlloyEditor.ezTableRowConfig(toolbarProps),
                            new eZ.ezAlloyEditor.ezTableCellConfig(toolbarProps),
                            new eZ.ezAlloyEditor.ezTableHeaderConfig(toolbarProps),
                            new eZ.ezAlloyEditor.ezEmbedImageLinkConfig(toolbarProps),
                            new eZ.ezAlloyEditor.ezEmbedImageConfig(toolbarProps),
                            new eZ.ezAlloyEditor.ezEmbedConfig(toolbarProps),
                            ...customSelections,
                        ],
                        tabIndex: 1,
                    },
                },
                extraPlugins:
                    AlloyEditor.Core.ATTRS.extraPlugins.value +
                    ',' +
                    [
                        'ezaddcontent',
                        'ezmoveelement',
                        'ezremoveblock',
                        'ezembed',
                        'ezembedinline',
                        'ezfocusblock',
                        'ezcustomtag',
                        'ezinlinecustomtag',
                        'ezelementspath',
                        ...this.alloyEditorExtraPlugins,
                    ].join(','),
            });
            const wrapper = this.getHTMLDocumentFragment(container.closest('.ez-data-source').querySelector('textarea').value);
            const section = wrapper.childNodes[0];
            const nativeEditor = alloyEditor.get('nativeEditor');
            const saveRichText = () => {
                const data = alloyEditor.get('nativeEditor').getData();
                const documentFragment = doc.createDocumentFragment();
                const root = doc.createElement('div');

                root.innerHTML = data;
                documentFragment.appendChild(root);

                documentFragment.querySelectorAll('[data-ezelement="ezembed"]').forEach(this.emptyEmbed);
                documentFragment.querySelectorAll('[data-ezelement="ezembedinline"]').forEach(this.emptyEmbed);
                documentFragment.querySelectorAll('[data-ezelement="eztemplate"]:not([data-eztype="style"])').forEach(this.clearCustomTag);
                documentFragment.querySelectorAll('.ez-has-anchor').forEach(this.clearAnchor);
                documentFragment
                    .querySelectorAll('[data-ezelement="eztemplateinline"]:not([data-eztype="style"])')
                    .forEach(this.clearInlineCustomTag);

                this.iterateThroughChildNodes(documentFragment, this.removeNodeInitializedState);

                container.closest('.ez-data-source').querySelector('textarea').value = this.xhtmlify(root.innerHTML).replace(
                    this.xhtmlNamespace,
                    this.ezNamespace
                );

                this.countWordsCharacters(container, documentFragment);
            };

            if (!section.hasChildNodes()) {
                section.appendChild(doc.createElement('p'));
            }

            nativeEditor.once('dataReady', () => container.querySelectorAll('.ez-has-anchor').forEach(this.appendAnchorIcon));

            this.iterateThroughChildNodes(section, this.setNodeInitializedState);
            this.countWordsCharacters(container, section);
            nativeEditor.setData(section.innerHTML);

            nativeEditor.on('blur', saveRichText);
            nativeEditor.on('change', saveRichText);
            nativeEditor.on('customUpdate', saveRichText);
            nativeEditor.on('editorInteraction', saveRichText);
            nativeEditor.on('afterPaste', () => {
                this.setLinksProtocol(container);
            });

            return alloyEditor;
        }

        setNodeInitializedState(node) {
            if (node.nodeType === HTML_NODE && !notInitializeElements.includes(node.nodeName.toLowerCase())) {
                node.setAttribute('data-ez-node-initialized', true);
            }
        }

        removeNodeInitializedState(node) {
            if (node.nodeType === HTML_NODE) {
                node.removeAttribute('data-ez-node-initialized');
            }
        }

        countWordsCharacters(container, editorHtml) {
            const counterWrapper = container.parentElement.querySelector('.ez-character-counter');

            if (counterWrapper) {
                const wordWrapper = counterWrapper.querySelector('.ez-character-counter__word-count');
                const charactersWrapper = counterWrapper.querySelector('.ez-character-counter__character-count');
                const words = this.getTextNodeValues(editorHtml);

                wordWrapper.innerText = words.length;
                charactersWrapper.innerText = words.join(' ').length;
            }
        }

        getTextNodeValues(node) {
            let values = [];

            const pushValue = (node) => {
                if (node.nodeType === TEXT_NODE) {
                    const nodeValue = this.sanitize(node.nodeValue);

                    values = values.concat(this.splitIntoWords(nodeValue));
                }
            };

            this.iterateThroughChildNodes(node, pushValue);

            return values;
        }

        iterateThroughChildNodes(node, callback) {
            if (typeof node.getAttribute === 'function' && node.getAttribute('data-ezelement') === 'ezconfig') {
                return;
            }
            callback(node);
            node = node.firstChild;

            while (node) {
                this.iterateThroughChildNodes(node, callback);
                node = node.nextSibling;
            }
        }

        sanitize(text) {
            return text.replace(/[\u200B-\u200D\uFEFF]/g, '');
        }

        splitIntoWords(text) {
            return text.split(' ').filter((word) => word.trim());
        }

        setLinksProtocol(container) {
            const links = container.querySelectorAll('a');
            const anchorPrefix = '#';
            const protocolPrefix = 'http://';

            links.forEach((link) => {
                const href = link.getAttribute('href');
                const schemaPattern = /^[a-z0-9]+:\/?\/?/i;
                const protocolHref = protocolPrefix.concat(href);

                if (!href) {
                    return;
                }

                if (href.indexOf(anchorPrefix) === 0) {
                    return;
                }

                if (schemaPattern.test(href)) {
                    return;
                }

                link.setAttribute('href', protocolHref);
                link.setAttribute('data-cke-saved-href', protocolHref);
            });
        }
    }

    eZ.BaseRichText = BaseRichText;
})(window, window.document, window.eZ, window.CKEDITOR, window.AlloyEditor);
