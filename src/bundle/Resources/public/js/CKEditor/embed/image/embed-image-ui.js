import IbexaEmbedBaseUI from '../embed-base-ui';

class IbexaEmbedImageUI extends IbexaEmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed_image';
        this.commandName = 'insertIbexaEmbedImage';
        this.buttonLabel = this.editor.t('Embed Image');
        this.componentName = 'ibexaEmbedImage';
    }

    getCommandOptions(items) {
        return {
            contentId: items[0].ContentInfo.Content._id,
            size: 'medium',
        };
    }
}

export default IbexaEmbedImageUI;
