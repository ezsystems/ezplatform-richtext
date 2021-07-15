import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaButtonView from '../common/button-view/button-view';

class IbexaEmbedBaseUI extends Plugin {
    constructor(props) {
        super(props);

        this.chooseContent = this.chooseContent.bind(this);
        this.confirmHandler = this.confirmHandler.bind(this);
        this.cancelHandler = this.cancelHandler.bind(this);

        this.configName = '';
        this.commandName = '';
        this.buttonLabel = '';
        this.componentName = '';
    }

    getCommandOptions() {}

    chooseContent() {
        const languageCode = document.querySelector('meta[name="LanguageCode"]').content;
        const config = JSON.parse(document.querySelector(`[data-udw-config-name="${this.configName}"]`).dataset.udwConfig);
        const selectContent = window.eZ.richText.alloyEditor.callbacks.selectContent;
        const mergedConfig = Object.assign(
            {
                onConfirm: this.confirmHandler,
                onCancel: this.cancelHandler,
                multiple: false,
            },
            config,
            {
                contentOnTheFly: {
                    allowedLanguages: [languageCode],
                },
            }
        );

        if (typeof selectContent === 'function') {
            selectContent(mergedConfig);
        }
    }

    confirmHandler(items) {
        this.editor.focus();
        this.editor.execute(this.commandName, this.getCommandOptions(items));
    }

    cancelHandler() {
        this.editor.focus();
    }

    init() {
        this.editor.ui.componentFactory.add(this.componentName, (locale) => {
            const buttonView = new IbexaButtonView(locale);

            buttonView.set({
                label: this.buttonLabel,
                icon: this.icon,
                tooltip: true,
            });

            this.listenTo(buttonView, 'execute', this.chooseContent);

            return buttonView;
        });
    }
}

export default IbexaEmbedBaseUI;
