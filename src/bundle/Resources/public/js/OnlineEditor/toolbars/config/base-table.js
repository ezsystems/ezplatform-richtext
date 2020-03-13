import AlloyEditor from 'alloyeditor';
import EzConfigButtonsBase from './base-buttons';

export default class EzConfigTableBase extends EzConfigButtonsBase {
    constructor(config) {
        super(config);

        this.name = this.getConfigName();
        this.buttons = this.getButtons(config);

        this.getArrowBoxClasses = AlloyEditor.SelectionGetArrowBoxClasses.table;
        this.setPosition = AlloyEditor.SelectionSetPosition.table;
    }

    getConfigName() {
        return '';
    }
}
