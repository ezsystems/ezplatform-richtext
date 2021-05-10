import InputTextView from '@ckeditor/ckeditor5-ui/src/inputtext/inputtextview';

export default class InputNumberView extends InputTextView {
    constructor(locale) {
        super(locale);

        const bind = this.bindTemplate;

        this.setTemplate({
            tag: 'input',
            attributes: {
                type: 'number',
                class: [
                    'ck',
                    'ck-input',
                    'ck-input-text',
                    bind.if('isFocused', 'ck-input_focused'),
                    bind.if('isEmpty', 'ck-input-text_empty'),
                    bind.if('hasError', 'ck-error'),
                ],
                id: bind.to('id'),
                placeholder: bind.to('placeholder'),
                readonly: bind.to('isReadOnly'),
                'aria-invalid': bind.if('hasError', true),
                'aria-describedby': bind.to('ariaDescribedById'),
            },
            on: {
                input: bind.to('input'),
                change: bind.to(this._updateIsEmpty.bind(this)),
            },
        });
    }
}
