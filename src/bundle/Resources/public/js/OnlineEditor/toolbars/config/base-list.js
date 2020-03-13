import EzConfigBase from './base';

export default class EzListBaseConfig extends EzConfigBase {
    constructor(config) {
        super(config);

        this.name = this.getConfigName();
        this.buttons = this.getButtons(config);
    }

    getConfigName() {
        return '';
    }
}
