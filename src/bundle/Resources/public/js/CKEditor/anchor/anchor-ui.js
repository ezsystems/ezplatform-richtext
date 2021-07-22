import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import clickOutsideHandler from '@ckeditor/ckeditor5-ui/src/bindings/clickoutsidehandler';

import IbexaAnchorFormView from './ui/anchor-form-view';
import IbexaButtonView from '../common/button-view/button-view';

class IbexaAnchorUI extends Plugin {
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
        const formView = new IbexaAnchorFormView({ locale: this.editor.locale });

        this.listenTo(formView, 'save-anchor', () => {
            const { anchor } = this.formView.getValues();
            const modelElement = this.getModelElement();

            this.editor.model.change((writer) => {
                writer.setAttribute('anchor', anchor, modelElement);
            });

            this.hideForm();
        });

        this.listenTo(formView, 'remove-anchor', () => {
            const modelElement = this.getModelElement();

            this.editor.model.change((writer) => {
                writer.removeAttribute('anchor', modelElement);
            });

            this.hideForm();
        });

        return formView;
    }

    showForm() {
        const parentElement = this.getModelElement();
        const values = {};

        if (parentElement) {
            values.anchor = parentElement.getAttribute('anchor') || '';
        }

        this.formView.setValues(values);

        this.balloon.add({
            view: this.formView,
            position: this.getBalloonPositionData(),
        });

        this.balloon.updatePosition(this.getBalloonPositionData());
    }

    hideForm() {
        this.balloon.remove(this.formView);
        this.editor.editing.view.focus();
    }

    getBalloonPositionData() {
        const view = this.editor.editing.view;
        const viewDocument = view.document;
        const range = viewDocument.selection.getFirstRange();

        return { target: view.domConverter.viewRangeToDom(range) };
    }

    enableUserBalloonInteractions() {
        clickOutsideHandler({
            emitter: this.formView,
            activator: () => this.balloon.hasView(this.formView),
            contextElements: [this.balloon.view.element],
            callback: () => this.hideForm(),
        });
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaAnchor', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Anchor")*/ 'anchor_btn.label', {}, 'ck_editor'),
                icon: window.eZ.helpers.icon.getIconPath('link-anchor'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.showForm);

            return buttonView;
        });

        this.editor.model.schema.extend('$block', {
            allowAttributes: ['id'],
        });

        this.enableUserBalloonInteractions();
    }
}

export default IbexaAnchorUI;
