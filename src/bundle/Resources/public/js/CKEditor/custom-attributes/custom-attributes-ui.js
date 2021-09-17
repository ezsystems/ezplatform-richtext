import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import clickOutsideHandler from '@ckeditor/ckeditor5-ui/src/bindings/clickoutsidehandler';

import IbexaCustomAttributesFormView from './ui/custom-attributes-form-view';
import IbexaButtonView from '../common/button-view/button-view';

class IbexaAttributesUI extends Plugin {
    constructor(props) {
        super(props);

        this.balloon = this.editor.plugins.get('ContextualBalloon');
        this.formView = this.createFormView();

        this.showForm = this.showForm.bind(this);
    }

    getModelElement() {
        return this.editor.model.document.selection.getSelectedElement() || this.editor.model.document.selection.anchor.parent;
    }

    createFormView() {
        const formView = new IbexaCustomAttributesFormView({ locale: this.editor.locale });

        this.listenTo(formView, 'save-custom-attributes', () => {
            const values = this.formView.getValues();
            const modelElement = this.getModelElement();

            this.editor.model.change((writer) => {
                Object.entries(values).forEach(([name, value]) => {
                    writer.setAttribute(name, value, modelElement);
                });
            });

            this.hideForm();
        });

        this.listenTo(formView, 'remove-custom-attributes', () => {
            const values = this.formView.getValues();
            const modelElement = this.getModelElement();

            this.editor.model.change((writer) => {
                Object.keys(values).forEach((name) => {
                    writer.removeAttribute(name, modelElement);
                });
            });

            this.hideForm();
        });

        return formView;
    }

    hideForm() {
        this.balloon.remove(this.formView);
        this.editor.editing.view.focus();
    }

    showForm() {
        const parentElement = this.getModelElement();
        const customAttributes = window.eZ.richText.alloyEditor.attributes[parentElement.name];
        const customClasses = window.eZ.richText.alloyEditor.classes[parentElement.name];
        const areCustomAttributesSet =
            parentElement.hasAttribute('custom-classes') ||
            Object.keys(customAttributes).some((customAttributeName) => parentElement.hasAttribute(customAttributeName));
        const attributesValues = Object.entries(customAttributes).reduce((output, [name, config]) => {
            output[name] = areCustomAttributesSet ? parentElement.getAttribute(name) : config.defaultValue;

            return output;
        }, {});
        const defaultCustomClasses = customClasses?.defaultValue ?? '';
        const classesValue = areCustomAttributesSet ? parentElement.getAttribute('custom-classes') : defaultCustomClasses;

        this.formView.destroy();
        this.formView = this.createFormView();

        this.formView.setChildren(customAttributes, customClasses);
        this.formView.setValues(attributesValues, classesValue);

        this.balloon.add({
            view: this.formView,
            position: this.getBalloonPositionData(),
        });

        this.balloon.updatePosition(this.getBalloonPositionData());
    }

    getBalloonPositionData() {
        const view = this.editor.editing.view;
        const viewDocument = view.document;
        const range = viewDocument.selection.getFirstRange();

        return { target: view.domConverter.viewRangeToDom(range) };
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaCustomAttributes', (locale) => {
            const buttonView = new IbexaButtonView(locale);
            const command = this.editor.commands.get('insertIbexaCustomAttributes');

            buttonView.set({
                label: Translator.trans(/*@Desc("Custom attributes")*/ 'custom_attributes_btn.label', {}, 'ck_editor'),
                icon: window.eZ.helpers.icon.getIconPath('edit'),
                tooltip: true,
            });

            buttonView.bind('isEnabled').to(command);

            this.listenTo(buttonView, 'execute', this.showForm);

            return buttonView;
        });

        clickOutsideHandler({
            emitter: this.formView,
            activator: () => this.balloon.hasView(this.formView),
            contextElements: [this.balloon.view.element],
            callback: () => this.hideForm(),
        });
    }
}

export default IbexaAttributesUI;
