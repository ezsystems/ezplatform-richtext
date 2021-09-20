import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import WidgetToolbarRepository from '@ckeditor/ckeditor5-widget/src/widgettoolbarrepository';

class IbexaEmbedImageToolbar extends Plugin {
    static get requires() {
        return [WidgetToolbarRepository];
    }

    getSelectedEmbedImageWidget(selection) {
        const viewElement = selection.getSelectedElement();
        const isEmbedImage = viewElement?.hasClass('ibexa-embed-type-image');

        return isEmbedImage ? viewElement : null;
    }

    afterInit() {
        const editor = this.editor;
        const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);

        widgetToolbarRepository.register('embedImage', {
            ariaLabel: editor.t('Embed Image toolbar'),
            items: editor.config.get('embedImage.toolbar') || [],
            getRelatedElement: this.getSelectedEmbedImageWidget,
        });
    }
}

export default IbexaEmbedImageToolbar;
