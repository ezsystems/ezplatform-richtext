import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaEmbedContentEditing from './content/embed-editing';
import IbexaEmbedContentInlineEditing from './content-inline/embed-inline-editing';
import IbexaEmbedImageEditing from './image/embed-image-editing';
import IbexaEmbedContentUI from './content/embed-ui';
import IbexaEmbedContentInlineUI from './content-inline/embed-inline-ui';
import IbexaEmbedImageUI from './image/embed-image-ui';
import IbexaEmbedImageToolbar from './image/embed-image-toolbar';
import IbexaEmbedImageVariationsUI from './image/embed-image-variations-ui';

class IbexaEmbed extends Plugin {
    static get requires() {
        return [
            IbexaEmbedContentEditing,
            IbexaEmbedContentInlineEditing,
            IbexaEmbedImageEditing,
            IbexaEmbedContentUI,
            IbexaEmbedContentInlineUI,
            IbexaEmbedImageUI,
            IbexaEmbedImageToolbar,
            IbexaEmbedImageVariationsUI,
        ];
    }
}

export default IbexaEmbed;
