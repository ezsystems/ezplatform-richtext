import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

class IbexaAnchorEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineConverters() {
        const { conversion } = this.editor;

        conversion.attributeToAttribute({
            model: {
                key: 'anchor',
            },
            view: {
                key: 'id',
            },
        });
    }

    init() {
        const { model } = this.editor;

        model.schema.extend('$block', { allowAttributes: 'anchor' });
        model.schema.extend('embed', { allowAttributes: 'anchor' });
        model.schema.extend('embedInline', { allowAttributes: 'anchor' });
        model.schema.extend('embedImage', { allowAttributes: 'anchor' });
        model.schema.extend('customTag', { allowAttributes: 'anchor' });

        this.defineConverters();
    }
}

export default IbexaAnchorEditing;
