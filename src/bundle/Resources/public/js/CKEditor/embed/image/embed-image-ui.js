import EmbedBaseUI from '../embed-base-ui';

class EmbedImageUI extends EmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed_image';
        this.commandName = 'insertEmbedImage';
        this.buttonLabel = this.editor.t('Embed Image');
        this.componentName = 'embedImage';
    }

    getCommandOptions(items) {
        return {
            contentId: items[0].ContentInfo.Content._id,
            size: 'medium',
        };
    }
}

export default EmbedImageUI;
