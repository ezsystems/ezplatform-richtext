import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import { toWidget } from '@ckeditor/ckeditor5-widget/src/utils';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

import EmbedContentInlineCommand from './embed-inline-command';

class EmbedContentInlineEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineSchema() {
        const schema = this.editor.model.schema;

        schema.register('embedInline', {
            isObject: true,
            isInline: true,
            allowWhere: '$text',
            allowAttributes: ['contentId', 'contentName'],
        });
    }

    defineConverters() {
        const conversion = this.editor.conversion;

        conversion
            .for('editingDowncast')
            .elementToElement({
                model: 'embedInline',
                view: (modelElement, { writer: downcastWriter }) => {
                    const container = downcastWriter.createContainerElement('span', {
                        'data-ezelement': 'ezembedinline',
                        'data-ezview': 'embed-inline',
                        class: 'ez-embed-inline',
                    });
                    const preview = downcastWriter.createUIElement('span', { class: 'ez-embed-content' }, function(domDocument) {
                        const domElement = this.toDomElement(domDocument);

                        domElement.innerHTML = `<svg class="ez-icon ez-icon--medium ez-icon--secondary">
                        <use xlink:href="${window.eZ.helpers.icon.getIconPath('embed')}"></use>
                        <span class="ez-embed-content__title">${modelElement.getAttribute('contentName')}</span>
                    </>`;

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
                    const preview = downcastWriter.createUIElement('span', { class: 'ez-embed-content' }, function(domDocument) {
                        const domElement = this.toDomElement(domDocument);

                        domElement.innerHTML = `<svg class="ez-icon ez-icon--medium ez-icon--secondary">
                        <use xlink:href="${window.eZ.helpers.icon.getIconPath('embed')}"></use>
                        <span class="ez-embed-content__title">${modelElement.getAttribute('contentName')}</span>
                    </>`;

                        return domElement;
                    });

                    downcastWriter.remove(downcastWriter.createRangeIn(viewElement));
                    downcastWriter.insert(downcastWriter.createPositionAt(viewElement, 0), preview);
                })
            );

        conversion.for('dataDowncast').elementToElement({
            model: 'embedInline',
            view: (modelElement, { writer: downcastWriter }) => {
                const container = downcastWriter.createContainerElement('span', {
                    'data-href': `ezcontent://${modelElement.getAttribute('contentId')}`,
                    'data-ezelement': 'ezembedinline',
                    'data-ezview': 'embed-inline',
                });

                return container;
            },
        });

        conversion.for('upcast').elementToElement({
            view: {
                name: 'span',
                attributes: {
                    'data-ezelement': 'ezembedinline',
                },
            },
            model: (viewElement, { writer: upcastWriter }) => {
                if (viewElement.hasClass('ez-embed-type-image')) {
                    return;
                }

                const href = viewElement.getAttribute('data-href');
                const contentId = href.replace('ezcontent://', '');
                const modelElement = upcastWriter.createElement('embedInline', { contentId });
                const token = document.querySelector('meta[name="CSRF-Token"]').content;
                const siteaccess = document.querySelector('meta[name="SiteAccess"]').content;
                const body = JSON.stringify({
                    ViewInput: {
                        identifier: `embed-load-content-info-${contentId}`,
                        public: false,
                        ContentQuery: {
                            FacetBuilders: {},
                            SortClauses: {},
                            Filter: { ContentIdCriterion: `${contentId}` },
                            limit: 1,
                            offset: 0,
                        },
                    },
                });
                const request = new Request('/api/ezp/v2/views', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/vnd.ez.api.View+json; version=1.1',
                        'Content-Type': 'application/vnd.ez.api.ViewInput+json; version=1.1',
                        'X-Siteaccess': siteaccess,
                        'X-CSRF-Token': token,
                    },
                    body,
                    mode: 'same-origin',
                    credentials: 'same-origin',
                });

                fetch(request)
                    .then((response) => response.json())
                    .then((hits) => {
                        const contentName = hits.View.Result.searchHits.searchHit[0].value.Content.TranslatedName;

                        this.editor.model.change((writer) => {
                            writer.setAttribute('contentName', contentName, modelElement);
                        });
                    })
                    .catch((error) => window.eZ.helpers.notification.showErrorNotification(error));

                return modelElement;
            },
        });
    }

    init() {
        this.defineSchema();
        this.defineConverters();

        this.editor.commands.add('insertEmbedInline', new EmbedContentInlineCommand(this.editor));
    }
}

export default EmbedContentInlineEditing;
