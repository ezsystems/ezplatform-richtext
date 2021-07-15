import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';

import IconView from '../icon-view/icon-view';

export default class IbexaButtonView extends ButtonView {
    constructor(locale) {
        super(locale);

        this.iconView = new IconView();

        this.iconView.extendTemplate({
            attributes: {
                class: 'ck-button__icon',
            },
        });
    }
}
