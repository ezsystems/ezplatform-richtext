import View from '@ckeditor/ckeditor5-ui/src/view';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import LabeledFieldView from '@ckeditor/ckeditor5-ui/src/labeledfield/labeledfieldview';

import Model from '@ckeditor/ckeditor5-ui/src/model';
import Collection from '@ckeditor/ckeditor5-utils/src/collection';
import { createLabeledInputText, createLabeledDropdown } from '@ckeditor/ckeditor5-ui/src/labeledfield/utils';
import { addListToDropdown } from '@ckeditor/ckeditor5-ui/src/dropdown/utils';

import { createLabeledInputNumber } from '../../common/input-number/utils';
import { createLabeledSwitchButton } from '../../common/switch-button/utils';

class IbexaCustomTagFormView extends View {
    constructor(props) {
        super(props);

        this.locale = props.locale;

        this.saveButtonView = this.createButton('Save', null, 'ck-button-save', 'save-custom-tag');
        this.cancelButtonView = this.createButton('Cancel', null, 'ck-button-cancel', 'cancel-custom-tag');

        this.attributeViews = {};
        this.attributeRenderMethods = {
            string: this.createTextInput,
            number: this.createNumberInput,
            choice: this.createDropdown,
            boolean: this.createBoolean,
            link: this.createTextInput,
        };
        this.setValueMethods = {
            string: this.setStringValue,
            number: this.setNumberValue,
            choice: this.setChoiceValue,
            boolean: this.setBooleanValue,
            link: this.setStringValue,
        };
    }

    setValues(values) {
        Object.entries(values).forEach(([name, value]) => {
            const attributeView = this.attributeViews[name];
            const setValueMethod = this.setValueMethods[this.childrenData.attributes[name].type];

            if (!attributeView || !setValueMethod) {
                return;
            }

            setValueMethod(attributeView, value);
        });
    }

    setNumberValue(attributeView, value) {
        attributeView.fieldView.element.value = value;
        attributeView.fieldView.set('value', value);
        attributeView.fieldView.set('isEmpty', value !== 0 && !value);
    }

    setStringValue(attributeView, value) {
        attributeView.fieldView.element.value = value;
        attributeView.fieldView.set('value', value);
        attributeView.fieldView.set('isEmpty', !value);
    }

    setChoiceValue(attributeView, value) {
        attributeView.fieldView.element.value = value;
        attributeView.fieldView.buttonView.set({
            label: value,
            withText: true,
        });
        attributeView.set('isEmpty', !value);
    }

    setBooleanValue(attributeView, value) {
        attributeView.fieldView.isOn = !!value || value === 'true';
        attributeView.fieldView.element.value = value;
        attributeView.fieldView.set('value', value);
        attributeView.fieldView.set('isEmpty', false);
    }

    setChildren(childrenData, label) {
        this.childrenData = childrenData;
        this.children = this.createFormChildren(childrenData);

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
                    children: [label],
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
                            children: this.children,
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

    createFormChildren({ attributes }) {
        const children = this.createCollection();

        Object.entries(attributes).forEach(([name, config]) => {
            const createAttributeMethod = this.attributeRenderMethods[config.type];

            if (!createAttributeMethod) {
                return;
            }

            const createAttribute = createAttributeMethod.bind(this);
            const attributeView = createAttribute(config);

            this.attributeViews[name] = attributeView;

            children.add(attributeView);
        });

        return children;
    }

    createDropdown(config) {
        const labeledDropdown = new LabeledFieldView(this.locale, createLabeledDropdown);
        const itemsList = new Collection();

        labeledDropdown.label = config.label;

        config.choices.forEach((choice) => {
            itemsList.add({
                type: 'button',
                model: new Model({
                    withText: true,
                    label: config.choicesLabel[choice],
                    value: choice,
                }),
            });
        });

        addListToDropdown(labeledDropdown.fieldView, itemsList);

        this.listenTo(labeledDropdown.fieldView, 'execute', (event) => {
            labeledDropdown.fieldView.buttonView.set({
                label: config.choicesLabel[event.source.value],
                withText: true,
            });

            labeledDropdown.fieldView.element.value = event.source.value;

            if (event.source.value) {
                labeledDropdown.set('isEmpty', false);
            }
        });

        return labeledDropdown;
    }

    createTextInput(config) {
        const labeledInput = new LabeledFieldView(this.locale, createLabeledInputText);

        labeledInput.label = config.label;

        return labeledInput;
    }

    createNumberInput(config) {
        const labeledInput = new LabeledFieldView(this.locale, createLabeledInputNumber);

        labeledInput.label = config.label;

        return labeledInput;
    }

    createBoolean(config) {
        const labeledSwitch = new LabeledFieldView(this.locale, createLabeledSwitchButton);

        this.listenTo(labeledSwitch.fieldView, 'execute', () => {
            const value = !labeledSwitch.fieldView.isOn;

            labeledSwitch.fieldView.element.value = value;
            labeledSwitch.fieldView.set('value', value);
            labeledSwitch.fieldView.isOn = value;
        });

        labeledSwitch.label = config.label;

        return labeledSwitch;
    }
}

export default IbexaCustomTagFormView;
