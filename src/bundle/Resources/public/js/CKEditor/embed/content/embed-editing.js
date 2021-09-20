import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import { toWidget } from '@ckeditor/ckeditor5-widget/src/utils';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

import IbexaEmbedContentCommand from './embed-command';

import { findContent } from '../../services/content-service';

const renderPreview = (title) => {
    return `<svg class="ibexa-icon ibexa-icon--medium ibexa-icon--secondary">
        <use xlink:href="${window.eZ.helpers.icon.getIconPath('embed')}"></use>
    </svg>
    <span class="ibexa-embed-content__title">${title}</span>`;
};

class IbexaEmbedContentEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineSchema() {
        const schema = this.editor.model.schema;

        schema.register('embed', {
            isObject: true,
            allowWhere: '$block',
            allowAttributes: ['contentId', 'contentName'],
        });
    }

    defineConverters() {
        const conversion = this.editor.conversion;

        conversion
            .for('editingDowncast')
            .elementToElement({
                model: 'embed',
                view: (modelElement, { writer: downcastWriter }) => {
                    const container = downcastWriter.createContainerElement('div', {
                        'data-ezelement': 'ezembed',
                        'data-ezview': 'embed',
                        class: 'ibexa-embed',
                    });
                    const preview = downcastWriter.createUIElement('p', { class: 'ibexa-embed-content' }, function(domDocument) {
                        const domElement = this.toDomElement(domDocument);

                        domElement.innerHTML = renderPreview(modelElement.getAttribute('contentName'));

                        return domElement;
                    });

                    downcastWriter.insert(downcastWriter.createPositionAt(container, 0), preview);

                    return toWidget(container, downcastWriter);
                },
            })
            .add((dispatcher) =>
                dispatcher.on('attribute:contentName', (event, data, conversionApi) => {
                    const downcastWriter = conversionApi.writer;
                    const modelElement = data.item;
                    const viewElement = conversionApi.mapper.toViewElement(modelElement);
                    const preview = downcastWriter.createUIElement('p', { class: 'ibexa-embed-content' }, function(domDocument) {
                        const domElement = this.toDomElement(domDocument);

                        domElement.innerHTML = renderPreview(modelElement.getAttribute('contentName'));

                        return domElement;
                    });

                    downcastWriter.remove(downcastWriter.createRangeIn(viewElement));
                    downcastWriter.insert(downcastWriter.createPositionAt(viewElement, 0), preview);
                })
            );

        conversion.for('dataDowncast').elementToElement({
            model: 'embed',
            view: (modelElement, { writer: downcastWriter }) => {
                const container = downcastWriter.createContainerElement('div', {
                    'data-href': `ezcontent://${modelElement.getAttribute('contentId')}`,
                    'data-ezelement': 'ezembed',
                    'data-ezview': 'embed',
                });

                return container;
            },
        });

        conversion.for('upcast').elementToElement({
            view: {
                name: 'div',
                attributes: {
                    'data-ezelement': 'ezembed',
                },
            },
            model: (viewElement, { writer: upcastWriter }) => {
                if (viewElement.hasClass('ibexa-embed-type-image')) {
                    return;
                }

                const href = viewElement.getAttribute('data-href');
                const contentId = href.replace('ezcontent://', '');
                const modelElement = upcastWriter.createElement('embed', { contentId });
                const token = document.querySelector('meta[name="CSRF-Token"]').content;
                const siteaccess = document.querySelector('meta[name="SiteAccess"]').content;

                findContent({ token, siteaccess, contentId }, (contents) => {
                    const contentName = contents[0].TranslatedName;

                    this.editor.model.change((writer) => {
                        writer.setAttribute('contentName', contentName, modelElement);
                    });
                });

                return modelElement;
            },
        });
    }

    init() {
        this.defineSchema();
        this.defineConverters();

        this.editor.commands.add('insertIbexaEmbed', new IbexaEmbedContentCommand(this.editor));
    }
}

export default IbexaEmbedContentEditing;
