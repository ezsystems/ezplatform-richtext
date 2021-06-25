import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';

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
            const buttonView = new ButtonView(locale);

            buttonView.set({
                label: 'formatted',
                tooltip: true,
                withText: true,
            });

            this.listenTo(buttonView, 'execute', this.addFormatted);

            return buttonView;
        });
    }
}

export default IbexaFormattedUI;
