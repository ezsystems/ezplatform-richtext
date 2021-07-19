import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import { createDropdown, addToolbarToDropdown } from '@ckeditor/ckeditor5-ui/src/dropdown/utils';

class IbexaCustomStyleInlineUI extends Plugin {
    constructor(props) {
        super(props);

        this.createButton = this.createButton.bind(this);
    }

    createButton([customStyleName, config]) {
        const editor = this.editor;

        this.editor.ui.componentFactory.add(customStyleName, (locale) => {
            const buttonView = new ButtonView(locale);

            buttonView.set({
                label: config.label,
                tooltip: true,
                isToggleable: true,
                withText: true,
            });

            this.listenTo(buttonView, 'execute', () => {
                editor.execute(customStyleName);
                editor.editing.view.focus();
            });

            return buttonView;
        });

        return this.editor.ui.componentFactory.create(customStyleName);
    }

    init() {
        this.editor.ui.componentFactory.add('ibexaCustomStyleInline', (locale) => {
            const dropdownView = createDropdown(locale);
            const customStylesInline = Object.entries(window.eZ.richText.customStyles).filter(([name, config]) => config.inline);
            const customStylesButtons = customStylesInline.map(this.createButton);

            dropdownView.buttonView.set({
                label: Translator.trans(/*@Desc("Custom styles")*/ 'custom_styles_btn.label', {}, 'ck_editor'),
                tooltip: true,
                withText: true,
            });

            addToolbarToDropdown(dropdownView, customStylesButtons);

            return dropdownView;
        });
    }
}

export default IbexaCustomStyleInlineUI;
