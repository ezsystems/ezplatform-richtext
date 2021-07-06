import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import clickOutsideHandler from '@ckeditor/ckeditor5-ui/src/bindings/clickoutsidehandler';

import IbexaCustomAttributesFormView from './ui/custom-attributes-form-view';

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
        const attributesValues = {};
        const customAttributes = window.eZ.richText.alloyEditor.attributes[parentElement.name];
        const customClasses = window.eZ.richText.alloyEditor.classes[parentElement.name];
        const areCustomAttributesSet =
            parentElement.hasAttribute('custom-classes') ||
            Object.keys(customAttributes).some((customAttributeName) => parentElement.hasAttribute(customAttributeName));
        let classesValue = areCustomAttributesSet ? parentElement.getAttribute('custom-classes') : customClasses.defaultValue;

        Object.entries(customAttributes).forEach(([name, config]) => {
            attributesValues[name] = areCustomAttributesSet ? parentElement.getAttribute(name) : config.defaultValue;
        });

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
            const buttonView = new ButtonView(locale);
            const command = this.editor.commands.get('insertIbexaCustomAttributes');

            buttonView.set({
                label: 'Custom attributes',
                tooltip: true,
                withText: true,
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
