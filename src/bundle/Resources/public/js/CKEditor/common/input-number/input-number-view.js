import InputTextView from '@ckeditor/ckeditor5-ui/src/inputtext/inputtextview';

export default class InputNumberView extends InputTextView {
    constructor(locale) {
        super(locale);

        const bindTemplate = this.bindTemplate;

        this.setTemplate({
            tag: 'input',
            attributes: {
                type: 'number',
                class: [
                    'ck',
                    'ck-input',
                    'ck-input-text',
                    bindTemplate.if('isFocused', 'ck-input_focused'),
                    bindTemplate.if('isEmpty', 'ck-input-text_empty'),
                    bindTemplate.if('hasError', 'ck-error'),
                ],
                id: bindTemplate.to('id'),
                placeholder: bindTemplate.to('placeholder'),
                readonly: bindTemplate.to('isReadOnly'),
                'aria-invalid': bindTemplate.if('hasError', true),
                'aria-describedby': bindTemplate.to('ariaDescribedById'),
            },
            on: {
                input: bindTemplate.to('input'),
                change: bindTemplate.to(this._updateIsEmpty.bind(this)),
            },
        });
    }
}
