import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';

import IbexaIconView from '../icon-view/icon-view';

export default class IbexaButtonView extends ButtonView {
    constructor(locale) {
        super(locale);

        this.iconView = new IbexaIconView();
    }
}
