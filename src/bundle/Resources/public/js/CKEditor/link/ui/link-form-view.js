import View from '@ckeditor/ckeditor5-ui/src/view';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import LabeledFieldView from '@ckeditor/ckeditor5-ui/src/labeledfield/labeledfieldview';
import { createLabeledInputText } from '@ckeditor/ckeditor5-ui/src/labeledfield/utils';

import { createLabeledSwitchButton } from '../../common/switch-button/utils';

class IbexaLinkFormView extends View {
    constructor(props) {
        super(props);

        this.locale = props.locale;

        this.saveButtonView = this.createButton('Save', null, 'ck-button-save', 'save-link');
        this.cancelButtonView = this.createButton('Remove link', null, 'ck-button-cancel', 'remove-link');
        this.selectContentButtonView = this.createButton('Select content', null, 'ibexa-btn--select-content');
        this.urlInputView = this.createTextInput('Link to');
        this.titleView = this.createTextInput('Title');
        this.targetSwitcherView = this.createBoolean('Open in tab');

        this.children = this.createFormChildren();

        this.setTemplate({
            tag: 'div',
            attributes: {
                class: 'ibexa-ckeditor-balloon-form',
            },
            children: [
                {
                    tag: 'div',
                    attributes: {
                        class: 'ibexa-ckeditor-balloon-form__header',
                    },
                    children: ['Link'],
                },
                {
                    tag: 'form',
                    attributes: {
                        tabindex: '-1',
                    },
                    children: [
                        {
                            tag: 'div',
                            attributes: {
                                class: 'ibexa-ckeditor-balloon-form__fields',
                            },
                            children: [
                                this.children.first,
                                {
                                    tag: 'div',
                                    attributes: {
                                        class: 'ibexa-ckeditor-balloon-form__separator',
                                    },
                                    children: ['Or'],
                                },
                                ...this.children.filter((child, index) => index !== 0),
                            ],
                        },
                        {
                            tag: 'div',
                            attributes: {
                                class: 'ibexa-ckeditor-balloon-form__actions',
                            },
                            children: [this.saveButtonView, this.cancelButtonView],
                        },
                    ],
                },
            ],
        });

        this.chooseContent = this.chooseContent.bind(this);
        this.confirmHandler = this.confirmHandler.bind(this);
        this.cancelHandler = this.cancelHandler.bind(this);

        this.listenTo(this.selectContentButtonView, 'execute', this.chooseContent);
    }

    setValues({ url, title, target }) {
        this.urlInputView.fieldView.element.value = url;
        this.urlInputView.fieldView.set('value', url);
        this.urlInputView.fieldView.set('isEmpty', !url);

        this.titleView.fieldView.element.value = title;
        this.titleView.fieldView.set('value', title);
        this.titleView.fieldView.set('isEmpty', !title);

        this.targetSwitcherView.fieldView.element.value = !!target;
        this.targetSwitcherView.fieldView.set('value', !!target);
        this.targetSwitcherView.fieldView.isOn = !!target;
        this.targetSwitcherView.fieldView.set('isEmpty', false);
    }

    getValues() {
        const url = this.setProtocol(this.urlInputView.fieldView.element.value);

        return {
            url,
            title: this.titleView.fieldView.element.value,
            target: this.targetSwitcherView.fieldView.isOn ? '_blank' : '',
        };
    }

    setProtocol(href) {
        if (!href) {
            return;
        }

        const anchorPrefix = '#';
        const schemaPattern = /^[a-z0-9]+:\/?\/?/i;
        const isAnchor = href.indexOf(anchorPrefix) === 0;
        const isLocation = schemaPattern.test(href);

        if (isAnchor || isLocation) {
            return href;
        }

        return `http://${href}`;
    }

    createFormChildren() {
        const children = this.createCollection();

        children.add(this.selectContentButtonView);
        children.add(this.urlInputView);
        children.add(this.titleView);
        children.add(this.targetSwitcherView);

        return children;
    }

    createTextInput(label) {
        const labeledInput = new LabeledFieldView(this.locale, createLabeledInputText);

        labeledInput.label = label;

        return labeledInput;
    }

    createBoolean(label) {
        const labeledSwitch = new LabeledFieldView(this.locale, createLabeledSwitchButton);

        this.listenTo(labeledSwitch.fieldView, 'execute', () => {
            const value = !labeledSwitch.fieldView.isOn;

            labeledSwitch.fieldView.element.value = value;
            labeledSwitch.fieldView.set('value', value);
            labeledSwitch.fieldView.isOn = value;
        });

        labeledSwitch.label = label;
        labeledSwitch.fieldView.set('isEmpty', false);

        return labeledSwitch;
    }

    createButton(label, icon, className, eventName) {
        const button = new ButtonView(this.locale);

        button.set({
            label,
            icon,
            withText: true,
        });

        button.extendTemplate({
            attributes: {
                class: className,
            },
        });

        if (eventName) {
            button.delegate('execute').to(this, eventName);
        }

        return button;
    }

    chooseContent() {
        const languageCode = document.querySelector('meta[name="LanguageCode"]').content;
        const config = JSON.parse(document.querySelector(`[data-udw-config-name="richtext_embed"]`).dataset.udwConfig);
        const selectContent = window.eZ.richText.alloyEditor.callbacks.selectContent;
        const mergedConfig = Object.assign(
            {
                onConfirm: this.confirmHandler,
                onCancel: this.cancelHandler,
                multiple: false,
            },
            config,
            {
                contentOnTheFly: {
                    allowedLanguages: [languageCode],
                },
            }
        );

        if (typeof selectContent === 'function') {
            selectContent(mergedConfig);
        }
    }

    confirmHandler(items) {
        const url = `ezlocation://${items[0].id}`;

        this.urlInputView.fieldView.element.value = url;
        this.urlInputView.fieldView.set('value', url);
        this.urlInputView.fieldView.set('isEmpty', !url);
    }

    cancelHandler() {
        this.editor.focus();
    }
}

export default IbexaLinkFormView;
