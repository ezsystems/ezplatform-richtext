import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaRemoveElementCommand from './remove-element-command';

class IbexaRemoveElementEditing extends Plugin {
    static get requires() {
        return [];
    }

    init() {
        this.editor.commands.add('ibexaRemoveElement', new IbexaRemoveElementCommand(this.editor));
    }
}

export default IbexaRemoveElementEditing;
