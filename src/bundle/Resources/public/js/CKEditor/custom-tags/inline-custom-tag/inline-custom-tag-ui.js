import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import clickOutsideHandler from '@ckeditor/ckeditor5-ui/src/bindings/clickoutsidehandler';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';

import IbexaCustomTagFormView from '../ui/custom-tag-form-view';
import IbexaButtonView from '../../common/button-view/button-view';

class IbexaInlineCustomTagUI extends Plugin {
    constructor(props) {
        super(props);

        this.balloon = this.editor.plugins.get('ContextualBalloon');
        this.formView = this.createFormView();

        this.showForm = this.showForm.bind(this);
        this.addInlineCustomTag = this.addInlineCustomTag.bind(this);

        this.isNew = false;
    }

    isInlineCustomTagSelected() {
        const modelElement = this.editor.model.document.selection.getSelectedElement();

        return modelElement && modelElement.name === 'inlineCustomTag' && modelElement.getAttribute('customTagName') === this.componentName;
    }

    enableUserBalloonInteractions() {
        const viewDocument = this.editor.editing.view.document;

        this.listenTo(viewDocument, 'click', () => {
            if (this.isInlineCustomTagSelected()) {
                this.showForm();
            }
        });

        clickOutsideHandler({
            emitter: this.formView,
            activator: () => this.balloon.hasView(this.formView),
            contextElements: [this.balloon.view.element],
            callback: () => this.hideForm(),
        });
    }

    createFormView() {
        const formView = new IbexaCustomTagFormView({ locale: this.editor.locale });

        this.listenTo(formView, 'save-custom-tag', () => {
            const modelElement = this.editor.model.document.selection.getSelectedElement();
            const values = modelElement.getAttribute('values');
            const newValues = { ...values };

            this.isNew = false;

            Object.keys(values).forEach((name) => {
                const attributeView = this.formView.attributeViews[name];

                if (!attributeView) {
                    return;
                }

                newValues[name] = attributeView.fieldView.element.value;
            });

            this.editor.model.change((writer) => {
                writer.setAttribute('values', newValues, modelElement);
            });

            this.hideForm();
        });

        this.listenTo(formView, 'cancel-custom-tag', () => {
            this.hideForm();
        });

        return formView;
    }

    showForm() {
        const modelElement = this.editor.model.document.selection.getSelectedElement();
        const values = modelElement.getAttribute('values');

        this.formView.setValues(values);

        this.balloon.add({
            view: this.formView,
            position: this.getBalloonPositionData(),
        });

        this.balloon.updatePosition(this.getBalloonPositionData());
    }

    hideForm() {
        if (this.isNew) {
            const modelElement = this.editor.model.document.selection.getSelectedElement();

            this.isNew = false;
            this.editor.model.change((writer) => {
                writer.remove(modelElement);
            });
        }

        this.balloon.remove(this.formView);
        this.editor.editing.view.focus();
    }

    getBalloonPositionData() {
        const view = this.editor.editing.view;
        const viewDocument = view.document;
        const range = viewDocument.selection.getFirstRange();

        return { target: view.domConverter.viewRangeToDom(range) };
    }

    addInlineCustomTag() {
        const values = Object.entries(this.config.attributes).reduce((values, [attributeName, config]) => {
            values[attributeName] = config.defaultValue;

            return values;
        }, {});

        this.editor.focus();
        this.editor.execute('insertIbexaInlineCustomTag', { customTagName: this.componentName, values });

        this.isNew = true;

        this.showForm();
    }

    init() {
        this.editor.ui.componentFactory.add(this.componentName, (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: this.config.label,
                icon: this.config.icon,
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.addInlineCustomTag);

            return buttonView;
        });

        this.editor.editing.view.addObserver(ClickObserver);

        this.enableUserBalloonInteractions();
    }
}

export default IbexaInlineCustomTagUI;
