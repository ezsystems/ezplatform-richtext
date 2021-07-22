import IbexaEmbedBaseUI from '../embed-base-ui';

class IbexaEmbedContentInlineUI extends IbexaEmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed';
        this.commandName = 'insertIbexaEmbedInline';
        this.buttonLabel = Translator.trans(/*@Desc("Embed")*/ 'embed_btn.label', {}, 'ck_editor');
        this.componentName = 'ibexaEmbedInline';
        this.icon = window.eZ.helpers.icon.getIconPath('embed');
    }

    getCommandOptions(items) {
        return {
            contentId: items[0].ContentInfo.Content._id,
            contentName: items[0].ContentInfo.Content.TranslatedName,
        };
    }
}

export default IbexaEmbedContentInlineUI;
