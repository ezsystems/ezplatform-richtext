import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaButtonView from '../common/button-view/button-view';

class IbexaMoveUpUI extends Plugin {
    constructor(props) {
        super(props);

        this.moveUp = this.moveUp.bind(this);
    }

    moveUp() {
        this.editor.execute('insertIbexaMove', { up: true });
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaMoveUp', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Move up")*/ 'move_up_btn.title', {}, 'ck_editor'),
                icon: window.eZ.helpers.icon.getIconPath('circle-caret-up'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.moveUp);

            return buttonView;
        });
    }
}

export default IbexaMoveUpUI;
