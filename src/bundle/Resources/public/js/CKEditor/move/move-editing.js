import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaLinkCommand from './move-command';

class IbexaCustomTagEditing extends Plugin {
    static get requires() {
        return [];
    }

    init() {
        this.editor.commands.add('insertIbexaMove', new IbexaLinkCommand(this.editor));
    }
}

export default IbexaCustomTagEditing;
