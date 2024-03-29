import AlloyEditor from 'alloyeditor';
import EzConfigButtonsBase from './base-buttons';

export default class EzLinkConfig extends EzConfigButtonsBase {
    constructor(config) {
        super(config);

        this.name = 'link';
        this.buttons = this.getButtons(config);

        this.test = AlloyEditor.SelectionTest.link;
    }

    /**
     * Returns the arrow box classes for the toolbar. The toolbar is
     * always positioned above its related block and has a special class to
     * move its tail on the left.
     *
     * @method getArrowBoxClasses
     * @return {String}
     */
    getArrowBoxClasses() {
        return 'ae-arrow-box ae-arrow-box-bottom';
    }

    /**
     * Sets the position of the toolbar. It overrides the default styles
     * toolbar positioning to position the toolbar just above its related
     * block element. The related block element is the block indicated in
     * CKEditor's path or the target of the editorEvent event.
     *
     * @method setPosition
     * @param {Object} payload
     * @param {AlloyEditor.Core} payload.editor
     * @param {Object} payload.selectionData
     * @param {Object} payload.editorEvent
     * @return {Boolean} true if the method was able to position the
     * toolbar
     */
    setPosition(payload) {
        const domElement = new CKEDITOR.dom.element(ReactDOM.findDOMNode(this));
        const region = payload.selectionData.region;
        const xy = this.getWidgetXYPoint(region.left, region.top, CKEDITOR.SELECTION_BOTTOM_TO_TOP);
        const elementWidth = domElement.$.offsetWidth;
        const parentWidth = domElement.$.parentElement.offsetWidth;
        const width = elementWidth + xy[0];
        let moveLeft = xy[0];

        if (parentWidth <= width) {
            moveLeft = (parentWidth - elementWidth) - 10;
        }

        domElement.addClass('ae-toolbar-transition');
        domElement.setStyles({ left: moveLeft + 'px', top: xy[1] + 'px' });

        return true;
    }
}

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezLinkConfig = EzLinkConfig;
