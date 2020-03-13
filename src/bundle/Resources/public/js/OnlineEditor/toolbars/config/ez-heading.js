import EzConfigFixedBase from './base-fixed';

export default class EzHeadingConfig extends EzConfigFixedBase {
    constructor(config) {
        super(config);

        this.name = 'heading';
        this.buttons = this.getButtons(config);
    }

    /**
     * Tests whether the `paragraph` toolbar should be visible. It is
     * visible when the selection is empty and when the caret is inside a
     * paragraph.
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
        const nativeEditor = payload.editor.get('nativeEditor');
        const path = nativeEditor.elementPath();

        return nativeEditor.isSelectionEmpty() && path && path.contains(['h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
    }
}

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezHeadingConfig = EzHeadingConfig;
