import IbexaEmbedBaseUI from '../embed-base-ui';

class IbexaEmbedContentUI extends IbexaEmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed';
        this.commandName = 'insertIbexaEmbed';
        this.buttonLabel = this.editor.t('Embed');
        this.componentName = 'ibexaEmbed';
    }

    getCommandOptions(items) {
        return {
            contentId: items[0].ContentInfo.Content._id,
            contentName: items[0].ContentInfo.Content.TranslatedName,
        };
    }
}

export default IbexaEmbedContentUI;
