import EzConfigBase from './base';

export default class EzCustomTagConfig extends EzConfigBase {
    constructor(config) {
        super(config);

        this.name = config.name;

        const buttons = this.getButtons(config);
        const customTagEditIndex = buttons.indexOf('ezcustomtagedit');

        if (customTagEditIndex > -1) {
            buttons[customTagEditIndex] = !!config.alloyEditor.attributes ? `${config.name}edit` : '';
        }

        this.buttons = buttons;

        this.test = this.test.bind(this);
    }

    /**
     * Tests whether the `embed` toolbar should be visible, it is visible
     * when an ezembed widget gets the focus.
     *
     * @method test
     * @param {Object} payload
     * @param {AlloyEditor.Core} payload.editor
     * @param {Object} payload.data
     * @param {Object} payload.data.selectionData
     * @param {Event} payload.data.nativeEvent
     * @return {Boolean}
     */
    test(payload) {
        const element = payload.data.selectionData.element;

        return !!(element && element.$.dataset.ezname == this.name);
    }
}

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezCustomTagConfig = EzCustomTagConfig;
