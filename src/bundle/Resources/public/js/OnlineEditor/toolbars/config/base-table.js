import EzConfigFixedBase from './base-fixed';

export default class EzConfigTableBase extends EzConfigFixedBase {
    constructor(config) {
        super(config);

        this.name = this.getConfigName();
        this.buttons = this.getButtons(config);
    }

    getConfigName() {
        return '';
    }

    getArrowBoxClasses() {
        return 'ae-toolbar-floating';
    }
}
