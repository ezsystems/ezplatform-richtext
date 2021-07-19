import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaButtonView from '../common/button-view/button-view';

class IbexaFormattedUI extends Plugin {
    constructor(props) {
        super(props);

        this.addFormatted = this.addFormatted.bind(this);
    }

    addFormatted() {
        this.editor.execute('insertIbexaFormatted');
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaFormatted', (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: Translator.trans(/*@Desc("Formatted")*/ 'formatted_btn.label', {}, 'ck_editor'),
                icon: window.eZ.helpers.icon.getIconPath('tag'),
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.addFormatted);

            return buttonView;
        });
    }
}

export default IbexaFormattedUI;
