import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import { toWidget } from '@ckeditor/ckeditor5-widget/src/utils';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

import IbexaEmbedImageCommand from './embed-image-command';

import { findContent } from '../../services/content-service';

class IbexaEmbedImageEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    constructor(props) {
        super(props);

        this.loadImagePreview = this.loadImagePreview.bind(this);
        this.loadImageVariation = this.loadImageVariation.bind(this);
    }

    loadImagePreview(modelElement) {
        const contentId = modelElement.getAttribute('contentId');
        const token = document.querySelector('meta[name="CSRF-Token"]').content;
        const siteaccess = document.querySelector('meta[name="SiteAccess"]').content;

        findContent({ token, siteaccess, contentId }, (contents) => {
            const fields = contents[0].CurrentVersion.Version.Fields.field;
            const fieldImage = fields.find((field) => field.fieldTypeIdentifier === 'ezimage');
            const size = modelElement.getAttribute('size');
            const variationHref = fieldImage.fieldValue.variations[size].href;

            this.loadImageVariation(modelElement, variationHref);
        });
    }

    loadImageVariation(modelElement, variationHref) {
        const token = document.querySelector('meta[name="CSRF-Token"]').content;
        const siteaccess = document.querySelector('meta[name="SiteAccess"]').content;
        const request = new Request(variationHref, {
            method: 'GET',
            headers: {
                Accept: 'application/vnd.ez.api.ContentImageVariation+json',
                'X-Siteaccess': siteaccess,
                'X-CSRF-Token': token,
            },
            credentials: 'same-origin',
            mode: 'same-origin',
        });

        fetch(request)
            .then((response) => response.json())
            .then((imageData) => {
                this.editor.model.change((writer) => {
                    writer.setAttribute('previewUrl', imageData.ContentImageVariation.uri, modelElement);
                });
            })
            .catch(window.eZ.helpers.notification.showErrorNotification);
    }

    defineSchema() {
        const schema = this.editor.model.schema;

        schema.register('embedImage', {
            isObject: true,
            allowWhere: '$block',
            allowAttributes: ['contentId', 'size'],
        });
    }

    defineConverters() {
        const conversion = this.editor.conversion;

        conversion
            .for('editingDowncast')
            .elementToElement({
                model: 'embedImage',
                view: (modelElement, { writer: downcastWriter }) => {
                    const container = downcastWriter.createContainerElement('div', {
                        'data-ezelement': 'ezembed',
                        'data-ezview': 'embed',
                        class: 'ibexa-embed-type-image',
                    });

                    this.loadImagePreview(modelElement);

                    return toWidget(container, downcastWriter);
                },
            })
            .add((dispatcher) =>
                dispatcher.on('attribute:previewUrl', (event, data, conversionApi) => {
                    const downcastWriter = conversionApi.writer;
                    const modelElement = data.item;
                    const viewElement = conversionApi.mapper.toViewElement(modelElement);
                    const preview = downcastWriter.createUIElement('img', { src: modelElement.getAttribute('previewUrl') }, function(
                        domDocument
                    ) {
                        const domElement = this.toDomElement(domDocument);

                        return domElement;
                    });

                    downcastWriter.remove(downcastWriter.createRangeIn(viewElement));
                    downcastWriter.insert(downcastWriter.createPositionAt(viewElement, 0), preview);
                })
            )
            .add((dispatcher) =>
                dispatcher.on('attribute:size', (event, data, conversionApi) => {
                    const modelElement = data.item;

                    this.loadImagePreview(modelElement);
                })
            );

        conversion.for('dataDowncast').elementToElement({
            model: 'embedImage',
            view: (modelElement, { writer: downcastWriter }) => {
                const container = downcastWriter.createContainerElement('div', {
                    'data-href': `ezcontent://${modelElement.getAttribute('contentId')}`,
                    'data-ezelement': 'ezembed',
                    'data-ezview': 'embed',
                    class: 'ibexa-embed-type-image',
                });
                const config = downcastWriter.createUIElement('span', { 'data-ezelement': 'ezconfig' }, function(domDocument) {
                    const domElement = this.toDomElement(domDocument);

                    domElement.innerHTML = `<span data-ezelement="ezvalue" data-ezvalue-key="size">
                        ${modelElement.getAttribute('size')}
                    </span>`;

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
                    'data-ezelement': 'ezembed',
                    class: 'ibexa-embed-type-image',
                },
            },
            model: (viewElement, { writer: upcastWriter }) => {
                const href = viewElement.getAttribute('data-href');
                const contentId = href.replace('ezcontent://', '');
                const size = viewElement
                    .getChild(0)
                    .getChild(0)
                    .getChild(0).data;
                const modelElement = upcastWriter.createElement('embedImage', { contentId, size });

                return modelElement;
            },
        });
    }

    init() {
        this.defineSchema();
        this.defineConverters();

        this.editor.commands.add('insertIbexaEmbedImage', new IbexaEmbedImageCommand(this.editor));
    }
}

export default IbexaEmbedImageEditing;
