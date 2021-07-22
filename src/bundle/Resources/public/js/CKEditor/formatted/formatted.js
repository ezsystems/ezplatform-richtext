import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaFormattedEditing from './formatted-editing';
import IbexaFormattedUI from './formatted-ui';

class IbexaFormatted extends Plugin {
    static get requires() {
        return [IbexaFormattedEditing, IbexaFormattedUI];
    }
}

export default IbexaFormatted;
