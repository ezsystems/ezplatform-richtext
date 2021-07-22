import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaLinkUI from './link-ui';
import IbexaLinkEditing from './link-editing';

class IbexaLink extends Plugin {
    static get requires() {
        return [IbexaLinkUI, IbexaLinkEditing];
    }
}

export default IbexaLink;
