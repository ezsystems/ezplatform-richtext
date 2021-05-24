import IbexaEmbedBaseUI from '../embed-base-ui';

class IbexaEmbedContentInlineUI extends IbexaEmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed';
        this.commandName = 'insertIbexaEmbedInline';
        this.buttonLabel = this.editor.t('Embed Inline');
        this.componentName = 'ibexaEmbedInline';
    }

    getCommandOptions(items) {
        return {
            contentId: items[0].ContentInfo.Content._id,
            contentName: items[0].ContentInfo.Content.TranslatedName,
        };
    }
}

export default IbexaEmbedContentInlineUI;
