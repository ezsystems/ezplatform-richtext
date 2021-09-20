import 'regenerator-runtime';

import IbexaCharacterCounter from '../plugins/character-counter';
import IbexaElementsPath from '../plugins/elements-path';
import IbexaEmbed from '../embed/embed';
import IbexaCustomTags from '../custom-tags/custom-tags';
import IbexaCustomStylesInline from '../custom-styles/inline/custom-styles-inline';
import IbexaCustomAttributes from '../custom-attributes/custom-attributes';
import IbexaLink from '../link/link';
import IbexaAnchor from '../anchor/anchor';
import IbexaFormatted from '../formatted/formatted';
import IbexaMove from '../move/move';
import IbexaRemoveElement from '../remove-element/remove-element';

import CKEditor from '@ckeditor/ckeditor5-editor-inline/src/inlineeditor';
import Essentials from '@ckeditor/ckeditor5-essentials/src/essentials';
import Alignment from '@ckeditor/ckeditor5-alignment/src/alignment';
import Heading from '@ckeditor/ckeditor5-heading/src/heading';
import ListStyle from '@ckeditor/ckeditor5-list/src/liststyle';
import Table from '@ckeditor/ckeditor5-table/src/table';
import TableToolbar from '@ckeditor/ckeditor5-table/src/tabletoolbar';
import Bold from '@ckeditor/ckeditor5-basic-styles/src/bold';
import Italic from '@ckeditor/ckeditor5-basic-styles/src/italic';
import Underline from '@ckeditor/ckeditor5-basic-styles/src/underline';
import Subscript from '@ckeditor/ckeditor5-basic-styles/src/subscript';
import Superscript from '@ckeditor/ckeditor5-basic-styles/src/superscript';
import Strikethrough from '@ckeditor/ckeditor5-basic-styles/src/strikethrough';
import BlockQuote from '@ckeditor/ckeditor5-block-quote/src/blockquote';
import ContextualBalloon from '@ckeditor/ckeditor5-ui/src/panel/balloon/contextualballoon';

const VIEWPORT_TOP_OFFSET = 102;

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
            const wrapper = this.getHTMLDocumentFragment(container.closest('.ibexa-data-source').querySelector('textarea').value);
            const section = wrapper.childNodes[0];
            const customTags = Object.keys(window.eZ.richText.customTags);
            const inlineCustomStyles = Object.entries(window.eZ.richText.customStyles).filter(
                ([customStyleName, customStyleConfig]) => customStyleConfig.inline
            );
            const blockCustomStyles = Object.entries(eZ.richText.customStyles)
                .filter(([customStyleName, customStyleConfig]) => !customStyleConfig.inline)
                .map(([customStyleName, customStyleConfig]) => {
                    return {
                        model: customStyleName,
                        view: {
                            name: 'div',
                            attributes: {
                                'data-ezelement': 'eztemplate',
                                'data-eztype': 'style',
                                'data-ezname': customStyleName,
                            },
                        },
                        title: customStyleConfig.label,
                    };
                });

            // Temp hack - to be removed when the configuration is done
            const customStyleInlineToolbar = inlineCustomStyles.length === 0 ? [] : ['ibexaCustomStyleInline'];

            if (!section.hasChildNodes()) {
                section.appendChild(doc.createElement('p'));
            }

            CKEditor.create(container, {
                initialData: section.innerHTML,
                plugins: [
                    Essentials,
                    Heading,
                    Alignment,
                    ListStyle,
                    Table,
                    TableToolbar,
                    Bold,
                    Italic,
                    Underline,
                    Subscript,
                    Superscript,
                    Strikethrough,
                    BlockQuote,
                    ContextualBalloon,
                    IbexaCharacterCounter,
                    IbexaElementsPath,
                    IbexaEmbed,
                    IbexaCustomTags,
                    IbexaCustomStylesInline,
                    IbexaCustomAttributes,
                    IbexaLink,
                    IbexaAnchor,
                    IbexaFormatted,
                    IbexaMove,
                    IbexaRemoveElement,
                ],
                toolbar: {
                    items: [
                        'ibexaMoveUp',
                        'ibexaMoveDown',
                        'heading',
                        '|',
                        'alignment',
                        '|',
                        'bulletedList',
                        'numberedList',
                        'insertTable',
                        '|',
                        'bold',
                        'italic',
                        'underline',
                        'subscript',
                        'superscript',
                        'strikethrough',
                        'blockQuote',
                        'ibexaLink',
                        'ibexaAnchor',
                        ...customStyleInlineToolbar,
                        'ibexaFormatted',
                        'ibexaCustomAttributes',
                        '|',
                        'ibexaEmbed',
                        'ibexaEmbedImage',
                        'ibexaEmbedInline',
                        '|',
                        ...customTags,
                    ],
                    viewportTopOffset: VIEWPORT_TOP_OFFSET,
                },
                embedImage: {
                    toolbar: ['imageVarations', 'ibexaRemoveElement'],
                },
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: { name: 'h1' }, title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: { name: 'h2' }, title: 'Heading 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: { name: 'h3' }, title: 'Heading 3', class: 'ck-heading_heading3' },
                        { model: 'heading4', view: { name: 'h4' }, title: 'Heading 4', class: 'ck-heading_heading4' },
                        { model: 'heading5', view: { name: 'h5' }, title: 'Heading 5', class: 'ck-heading_heading5' },
                        { model: 'heading6', view: { name: 'h6' }, title: 'Heading 6', class: 'ck-heading_heading6' },
                        ...blockCustomStyles,
                    ],
                },
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
                },
            }).then((editor) => {
                this.editor = editor;

                this.editor.model.document.on('change:data', () => {
                    const data = this.getData();

                    container.closest('.ibexa-data-source').querySelector('textarea').value = this.xhtmlify(data).replace(
                        this.xhtmlNamespace,
                        this.ezNamespace
                    );
                });
            });
        }
    }

    eZ.BaseRichText = BaseRichText;
})(window, window.document, window.eZ);
