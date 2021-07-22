import IbexaEmbedBaseUI from '../embed-base-ui';

class IbexaEmbedImageUI extends IbexaEmbedBaseUI {
    constructor(props) {
        super(props);

        this.configName = 'richtext_embed_image';
        this.commandName = 'insertIbexaEmbedImage';
        this.buttonLabel = Translator.trans(/*@Desc("Image")*/ 'image_btn.label', {}, 'ck_editor');
        this.componentName = 'ibexaEmbedImage';
        this.icon = window.eZ.helpers.icon.getIconPath('image');
    }

    getCommandOptions(items) {
        return {
            contentId: items[0].ContentInfo.Content._id,
            size: 'medium',
        };
    }
}

export default IbexaEmbedImageUI;
