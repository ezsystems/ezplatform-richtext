import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaButtonView from '../common/button-view/button-view';

class IbexaRemoveElementUI extends Plugin {
    constructor(props) {
        super(props);

        this.removeBlock = this.removeBlock.bind(this);
    }

    removeBlock() {
        this.editor.execute('ibexaRemoveElement');
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaRemoveElement', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Remove")*/ 'remove_block.title', {}, 'ck_editor'),
                icon: window.eZ.helpers.icon.getIconPath('trash'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.removeBlock);

            return buttonView;
        });
    }
}

export default IbexaRemoveElementUI;
