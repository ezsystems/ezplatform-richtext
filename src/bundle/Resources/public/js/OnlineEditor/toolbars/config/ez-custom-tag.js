import EzConfigBase from './base';

export default class EzCustomTagConfig extends EzConfigBase {
    constructor(config) {
        super(config);

        const editButton = !!config.alloyEditor.attributes ? `${config.name}edit` : '';
        const defaultButtons = [
            'ezmoveup',
            'ezmovedown',
            editButton,
            'ezanchor',
            'ezembedleft',
            'ezembedcenter',
            'ezembedright',
            'ezblockremove',
        ];
        const customButtons = config.alloyEditor.toolbarButtons;
        const buttons = customButtons && customButtons.length ? customButtons : defaultButtons;

        this.name = config.name;
        this.buttons = buttons;

        this.addExtraButtons(config.extraButtons);

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
