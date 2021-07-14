import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';
import { toWidget, toWidgetEditable } from '@ckeditor/ckeditor5-widget/src/utils';

import IbexaCustomTagCommand from './custom-tag-command';

class IbexaCustomTagEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineSchema() {
        const { schema } = this.editor.model;

        schema.register('customTag', {
            isObject: true,
            allowWhere: '$block',
            allowAttributes: ['customTagName', 'values'],
        });

        schema.register('customTagContent', {
            isBlock: true,
            allowIn: 'customTag',
            allowContentOf: '$root',
        });
    }

    defineConverters() {
        const { conversion } = this.editor;

        conversion.for('editingDowncast').elementToElement({
            model: 'customTag',
            view: (modelElement, { writer: downcastWriter }) => {
                const container = downcastWriter.createContainerElement('div', {
                    'data-ezelement': 'eztemplate',
                    'data-ezname': modelElement.getAttribute('customTagName'),
                    class: 'ez-custom-tag',
                });

                return toWidget(container, downcastWriter);
            },
        });

        conversion.for('dataDowncast').elementToElement({
            model: 'customTag',
            view: (modelElement, { writer: downcastWriter }) => {
                const container = downcastWriter.createContainerElement('div', {
                    'data-ezelement': 'eztemplate',
                    'data-ezname': modelElement.getAttribute('customTagName'),
                });
                const config = downcastWriter.createUIElement('span', { 'data-ezelement': 'ezconfig' }, function(domDocument) {
                    const domElement = this.toDomElement(domDocument);

                    domElement.innerHTML = Object.entries(modelElement.getAttribute('values')).reduce((total, [attribute, value]) => {
                        const attributeValue = value !== null ? value : '';
                        const ezvalue = `<span data-ezelement="ezvalue" data-ezvalue-key="${attribute}">${attributeValue}</span>`;

                        return `${total}${ezvalue}`;
                    }, '');

                    return domElement;
                });

                downcastWriter.remove(downcastWriter.createRangeIn(container));
                downcastWriter.insert(downcastWriter.createPositionAt(container, 0), config);

                return container;
            },
        });

        conversion.for('upcast').elementToElement({
            view: {
                name: 'div',
                attributes: {
                    'data-ezelement': 'eztemplate',
                },
            },
            model: (viewElement, { writer: upcastWriter }) => {
                if (viewElement.getAttribute('data-eztype') === 'style') {
                    return;
                }

                const configElement = viewElement.getChild(1);
                const configValuesIterator = configElement.getChildren();
                const customTagName = viewElement.getAttribute('data-ezname');
                const values = {};

                for (const configValue of configValuesIterator) {
                    const value = (configValue.getChild(0) && configValue.getChild(0).data) || null;

                    values[configValue.getAttribute('data-ezvalue-key')] = value;
                }

                const modelElement = upcastWriter.createElement('customTag', { customTagName, values });

                return modelElement;
            },
        });

        conversion.for('editingDowncast').elementToElement({
            model: 'customTagContent',
            view: (modelElement, { writer: downcastWriter }) => {
                const div = downcastWriter.createEditableElement('div');

                return toWidgetEditable(div, downcastWriter);
            },
        });

        conversion.for('dataDowncast').elementToElement({
            model: 'customTagContent',
            view: {
                name: 'div',
                attributes: {
                    'data-ezelement': 'ezcontent',
                },
            },
        });

        conversion.for('upcast').elementToElement({
            model: 'customTagContent',
            view: {
                name: 'div',
                attributes: {
                    'data-ezelement': 'ezcontent',
                },
            },
        });
    }

    init() {
        this.defineSchema();
        this.defineConverters();

        this.editor.commands.add('insertIbexaCustomTag', new IbexaCustomTagCommand(this.editor));
    }
}

export default IbexaCustomTagEditing;
