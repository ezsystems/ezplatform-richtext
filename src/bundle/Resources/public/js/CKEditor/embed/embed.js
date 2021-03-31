import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import EmbedContentEditing from './content/embed-editing';
import EmbedContentInlineEditing from './content-inline/embed-inline-editing';
import EmbedImageEditing from './image/embed-image-editing';
import EmbedContentUI from './content/embed-ui';
import EmbedContentInlineUI from './content-inline/embed-inline-ui';
import EmbedImageUI from './image/embed-image-ui';
import EmbedImageToolbar from './image/embed-image-toolbar';
import EmbedImageVariationsUI from './image/embed-image-variations-ui';

class Embed extends Plugin {
    static get requires() {
        return [
            EmbedContentEditing,
            EmbedContentInlineEditing,
            EmbedImageEditing,
            EmbedContentUI,
            EmbedContentInlineUI,
            EmbedImageUI,
            EmbedImageToolbar,
            EmbedImageVariationsUI,
        ];
    }
}

export default Embed;
