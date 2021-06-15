import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaAnchorUI from './anchor-ui';
import IbexaAnchorEditing from './anchor-editing';

class IbexaAnchor extends Plugin {
    static get requires() {
        return [IbexaAnchorUI, IbexaAnchorEditing];
    }
}

export default IbexaAnchor;
