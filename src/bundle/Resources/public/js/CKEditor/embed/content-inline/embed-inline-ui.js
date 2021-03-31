import EmbedBaseUI from '../embed-base-ui';

class EmbedContentInlineUI extends EmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed';
        this.commandName = 'insertEmbedInline';
        this.buttonLabel = this.editor.t('Embed Inlnie');
        this.componentName = 'embedInline';
    }

    getCommandOptions(items) {
        return {
            contentId: items[0].ContentInfo.Content._id,
            contentName: items[0].ContentInfo.Content.TranslatedName,
        };
    }
}

export default EmbedContentInlineUI;
