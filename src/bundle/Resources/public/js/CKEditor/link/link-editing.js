import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaLinkCommand from './link-command';

class IbexaCustomTagEditing extends Plugin {
    static get requires() {
        return [];
    }

    defineConverters() {
        const { conversion } = this.editor;

        conversion.for('editingDowncast').attributeToElement({
            model: 'ibexaLinkHref',
            view: (href, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { href }),
        });

        conversion.for('dataDowncast').attributeToElement({
            model: 'ibexaLinkHref',
            view: (href, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { href }),
        });

        conversion.for('editingDowncast').attributeToElement({
            model: 'ibexaLinkTitle',
            view: (title, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { title }),
        });

        conversion.for('dataDowncast').attributeToElement({
            model: 'ibexaLinkTitle',
            view: (title, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { title }),
        });

        conversion.for('editingDowncast').attributeToElement({
            model: 'ibexaLinkTarget',
            view: (target, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { target }),
        });

        conversion.for('dataDowncast').attributeToElement({
            model: 'ibexaLinkTarget',
            view: (target, { writer: downcastWriter }) => downcastWriter.createAttributeElement('a', { target }),
        });

        conversion.for('upcast').elementToAttribute({
            view: {
                name: 'a',
                attributes: {
                    href: true,
                },
            },
            model: {
                key: 'ibexaLinkHref',
                value: (viewElement) => viewElement.getAttribute('href'),
            },
        });

        conversion.for('upcast').attributeToAttribute({
            view: {
                name: 'a',
                key: 'title',
            },
            model: 'ibexaLinkTitle',
        });

        conversion.for('upcast').attributeToAttribute({
            view: {
                name: 'a',
                key: 'target',
            },
            model: 'ibexaLinkTarget',
        });
    }

    init() {
        this.editor.model.schema.extend('$text', { allowAttributes: 'ibexaLinkHref' });
        this.editor.model.schema.extend('$text', { allowAttributes: 'ibexaLinkTitle' });
        this.editor.model.schema.extend('$text', { allowAttributes: 'ibexaLinkTarget' });

        this.defineConverters();

        this.editor.commands.add('insertIbexaLink', new IbexaLinkCommand(this.editor));
    }
}

export default IbexaCustomTagEditing;
