import View from '@ckeditor/ckeditor5-ui/src/view';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import LabeledFieldView from '@ckeditor/ckeditor5-ui/src/labeledfield/labeledfieldview';
import { createLabeledInputText } from '@ckeditor/ckeditor5-ui/src/labeledfield/utils';

class IbexaLinkFormView extends View {
    constructor(props) {
        super(props);

        this.locale = props.locale;

        this.anchorInputView = this.createTextInput('Name');
        this.saveButtonView = this.createButton('Save anchor', null, 'ck-button-save', 'save-anchor');
        this.cancelButtonView = this.createButton('Remove anchor', null, 'ck-button-cancel', 'remove-anchor');

        this.children = this.createFormChildren();

        this.setTemplate({
            tag: 'form',
            attributes: {
                tabindex: '-1',
            },
            children: this.children,
        });
    }

    setValues({ anchor }) {
        this.anchorInputView.fieldView.element.value = anchor;
        this.anchorInputView.fieldView.set('value', anchor);
        this.anchorInputView.fieldView.set('isEmpty', !anchor);
    }

    getValues() {
        return {
            anchor: this.anchorInputView.fieldView.element.value,
        };
    }

    createFormChildren() {
        const children = this.createCollection();

        children.add(this.anchorInputView);
        children.add(this.saveButtonView);
        children.add(this.cancelButtonView);

        return children;
    }

    createTextInput(label) {
        const labeledInput = new LabeledFieldView(this.locale, createLabeledInputText);

        labeledInput.label = label;

        return labeledInput;
    }

    createButton(label, icon, className, eventName) {
        const button = new ButtonView(this.locale);

        button.set({
            label,
            icon,
            tooltip: true,
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
}

export default IbexaLinkFormView;
