import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Widget from '@ckeditor/ckeditor5-widget/src/widget';

import IbexaCustomAttributesCommand from './custom-attributes-command';

class IbexaCustomAttributesEditing extends Plugin {
    static get requires() {
        return [Widget];
    }

    defineConverters() {
        const { conversion } = this.editor;

        conversion.attributeToAttribute({
            model: {
                key: 'custom-classes',
            },
            view: {
                key: 'class',
            },
        });

        Object.values(window.eZ.richText.alloyEditor.attributes).forEach((customAttributes) => {
            Object.keys(customAttributes).forEach((customAttributeName) => {
                conversion.attributeToAttribute({
                    model: {
                        key: customAttributeName,
                    },
                    view: {
                        key: `data-ezattribute-${customAttributeName}`,
                    },
                });
            });
        });
    }

    init() {
        const { model } = this.editor;
        const elementsWithCustomAttributes = Object.keys(window.eZ.richText.alloyEditor.attributes);
        const elementsWithCustomClasses = Object.keys(window.eZ.richText.alloyEditor.classes);

        elementsWithCustomAttributes.forEach((element) => {
            const customAttributes = Object.keys(window.eZ.richText.alloyEditor.attributes[element]);

            model.schema.extend(element, { allowAttributes: customAttributes });
        });

        elementsWithCustomClasses.forEach((element) => {
            model.schema.extend(element, { allowAttributes: 'custom-classes' });
        });

        this.defineConverters();

        this.editor.commands.add('insertIbexaCustomAttributes', new IbexaCustomAttributesCommand(this.editor));
    }
}

export default IbexaCustomAttributesEditing;
