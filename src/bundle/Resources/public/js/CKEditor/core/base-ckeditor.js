import CharacterCounter from '../plugins/character-counter';
import InlineEditor from '../../../../../../../../ezplatform-admin-ui-assets/Resources/public/vendors/@ckeditor/ckeditor5-editor-inline/src/inlineeditor';
import Essentials from '../../../../../../../../ezplatform-admin-ui-assets/Resources/public/vendors/@ckeditor/ckeditor5-essentials/src/essentials';
import Paragraph from '../../../../../../../../ezplatform-admin-ui-assets/Resources/public/vendors/@ckeditor/ckeditor5-paragraph/src/paragraph';
import Bold from '../../../../../../../../ezplatform-admin-ui-assets/Resources/public/vendors/@ckeditor/ckeditor5-basic-styles/src/bold';
import Italic from '../../../../../../../../ezplatform-admin-ui-assets/Resources/public/vendors/@ckeditor/ckeditor5-basic-styles/src/italic';

(function(global, doc, eZ) {
    class BaseRichText {
        constructor(config) {
            this.ezNamespace = 'http://ez.no/namespaces/ezpublish5/xhtml5/edit';
            this.xhtmlNamespace = 'http://www.w3.org/1999/xhtml';

            this.editor = null;

            this.xhtmlify = this.xhtmlify.bind(this);
            this.getData = this.getData.bind(this);
        }

        getData() {
            return this.editor.getData();
        }

        getHTMLDocumentFragment(data) {
            const fragment = doc.createDocumentFragment();
            const div = fragment.ownerDocument.createElement('div');
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

            fragment.appendChild(div);

            importChildNodes(div, parsedHTML.documentElement, true);

            return fragment;
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

        init(container) {
            const wrapper = this.getHTMLDocumentFragment(container.closest('.ez-data-source').querySelector('textarea').value);
            const section = wrapper.childNodes[0];

            if (!section.hasChildNodes()) {
                section.appendChild(doc.createElement('p'));
            }

            InlineEditor.create(container, {
                initialData: section.innerHTML,
                plugins: [CharacterCounter, Essentials, Paragraph, Bold, Italic],
                toolbar: ['bold', 'italic'],
            }).then((editor) => {
                this.editor = editor;

                this.editor.model.document.on('change:data', () => {
                    const data = this.getData();

                    container.closest('.ez-data-source').querySelector('textarea').value = this.xhtmlify(data).replace(
                        this.xhtmlNamespace,
                        this.ezNamespace
                    );
                });
            });
        }
    }

    eZ.BaseRichText = BaseRichText;
})(window, window.document, window.eZ);
