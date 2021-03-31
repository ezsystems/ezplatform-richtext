import EmbedBaseUI from '../embed-base-ui';

class EmbedContentUI extends EmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed';
        this.commandName = 'insertEmbed';
        this.buttonLabel = this.editor.t('Embed');
        this.componentName = 'embed';
    }

    getCommandOptions(items) {
        return {
            contentId: items[0].ContentInfo.Content._id,
            contentName: items[0].ContentInfo.Content.TranslatedName,
        };
    }
}

export default EmbedContentUI;
