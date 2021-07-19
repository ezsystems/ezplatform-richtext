import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import clickOutsideHandler from '@ckeditor/ckeditor5-ui/src/bindings/clickoutsidehandler';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';
import findAttributeRange from '@ckeditor/ckeditor5-typing/src/utils/findattributerange';

import IbexaLinkFormView from './ui/link-form-view';
import IbexaButtonView from '../common/button-view/button-view';

class IbexaLinkUI extends Plugin {
    constructor(props) {
        super(props);

        this.balloon = this.editor.plugins.get('ContextualBalloon');
        this.formView = this.createFormView();

        this.showForm = this.showForm.bind(this);
        this.addLink = this.addLink.bind(this);

        this.isNew = false;
    }

    createFormView() {
        const formView = new IbexaLinkFormView({ locale: this.editor.locale });

        this.listenTo(formView, 'save-link', () => {
            const { url, title, target } = this.formView.getValues();

            this.isNew = false;

            this.removeLink();
            this.editor.execute('insertIbexaLink', { href: url, title: title, target: target });
            this.hideForm();
        });

        this.listenTo(formView, 'remove-link', () => {
            this.removeLink();
            this.hideForm();
        });

        return formView;
    }

    removeLink() {
        const range = findAttributeRange(
            this.editor.model.document.selection.getFirstPosition(),
            'ibexaLinkHref',
            this.editor.model.document.selection.getAttribute('ibexaLinkHref'),
            this.editor.model
        );

        this.editor.model.change((writer) => {
            writer.removeAttribute('ibexaLinkHref', range);
            writer.removeAttribute('ibexaLinkTitle', range);
            writer.removeAttribute('ibexaLinkTarget', range);

            writer.setSelection(range);
        });
    }

    showForm() {
        const link = this.findLinkElement();
        const values = {
            url: link ? link.getAttribute('href') : '',
            title: link ? link.getAttribute('title') : '',
            target: link ? link.getAttribute('target') : '',
        };

        this.formView.setValues(values);

        this.balloon.add({
            view: this.formView,
            position: this.getBalloonPositionData(),
        });

        this.balloon.updatePosition(this.getBalloonPositionData());
    }

    hideForm() {
        if (this.isNew) {
            this.editor.model.change((writer) => {
                const ranges = this.editor.model.schema.getValidRanges(this.editor.model.document.selection.getRanges(), 'ibexaLinkHref');

                for (const range of ranges) {
                    writer.removeAttribute('ibexaLinkHref', range);
                }
            });
        }

        this.balloon.remove(this.formView);
        this.editor.editing.view.focus();
    }

    addLink() {
        this.editor.focus();
        this.editor.execute('insertIbexaLink', { href: '', title: '', target: '' });

        this.isNew = true;

        this.showForm();
    }

    getBalloonPositionData() {
        const view = this.editor.editing.view;
        const viewDocument = view.document;
        const range = viewDocument.selection.getFirstRange();

        return { target: view.domConverter.viewRangeToDom(range) };
    }

    enableUserBalloonInteractions() {
        const viewDocument = this.editor.editing.view.document;

        this.listenTo(viewDocument, 'click', () => {
            if (this.isLinkSelected()) {
                this.showForm();
            }
        });

        clickOutsideHandler({
            emitter: this.formView,
            activator: () => this.balloon.hasView(this.formView),
            contextElements: [this.balloon.view.element, document.querySelector('#react-udw')],
            callback: () => this.hideForm(),
        });
    }

    findLinkElement() {
        const position = this.editor.editing.view.document.selection.getFirstPosition();
        const ancestors = position.getAncestors();
        const link = ancestors.find((ancestor) => ancestor.is('attributeElement') && !!ancestor.hasAttribute('href'));

        return link;
    }

    isLinkSelected() {
        return !!this.findLinkElement();
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaLink', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Link")*/ 'link_btn.label', {}, 'ck_editor'),
                icon: window.eZ.helpers.icon.getIconPath('link'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.addLink);

            return buttonView;
        });

        this.editor.editing.view.addObserver(ClickObserver);

        this.enableUserBalloonInteractions();
    }
}

export default IbexaLinkUI;
