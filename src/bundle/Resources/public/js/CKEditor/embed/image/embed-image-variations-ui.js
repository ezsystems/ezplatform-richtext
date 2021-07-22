import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import { createDropdown, addListToDropdown } from '@ckeditor/ckeditor5-ui/src/dropdown/utils';
import Model from '@ckeditor/ckeditor5-ui/src/model';
import Collection from '@ckeditor/ckeditor5-utils/src/collection';

class IbexaEmbedImageVariationsUI extends Plugin {
    constructor(props) {
        super(props);

        this.changeVariation = this.changeVariation.bind(this);
    }

    changeVariation(dropdownView, event) {
        const modelElement = this.getSelectedElement();
        const variation = event.source.variation;

        dropdownView.buttonView.set({
            label: variation,
        });

        this.editor.model.change((writer) => {
            writer.setAttribute('size', variation, modelElement);
        });
    }

    getSelectedElement() {
        return this.editor.model.document.selection.getSelectedElement();
    }

    init() {
        this.editor.ui.componentFactory.add('imageVarations', (locale) => {
            const dropdownView = createDropdown(locale);
            const itemDefinitions = new Collection();

            Object.keys(window.eZ.adminUiConfig.imageVariations).forEach((variation) => {
                itemDefinitions.add({
                    type: 'button',
                    model: new Model({
                        label: variation,
                        variation: variation,
                        withText: true,
                    }),
                });
            });

            dropdownView.buttonView.set({
                isOn: true,
                withText: true,
            });

            addListToDropdown(dropdownView, itemDefinitions);

            this.editor.model.document.selection.on('change:range', () => {
                const modelElement = this.getSelectedElement();

                if (modelElement && modelElement.name === 'embedImage') {
                    dropdownView.buttonView.set({
                        label: modelElement.getAttribute('size'),
                    });
                }
            });

            this.listenTo(dropdownView, 'execute', this.changeVariation.bind(this, dropdownView));

            return dropdownView;
        });
    }
}

export default IbexaEmbedImageVariationsUI;
