import AlloyEditor from 'alloyeditor';
import EzConfigButtonsBase from './base-buttons';

export default class EzTextConfig extends EzConfigButtonsBase {
    constructor(config) {
        super(config);

        this.name = 'text';
        this.buttons = this.getButtons(config);

        this.test = AlloyEditor.SelectionTest.text;
    }

    getStyles(customStyles = []) {
        return {
            name: 'styles',
            cfg: {
                showRemoveStylesItem: true,
                styles: [...customStyles],
            },
        };
    }
}

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezTextConfig = EzTextConfig;
