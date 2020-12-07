import ReactDOM from 'react-dom';
import EzConfigButtonsBase from './base-buttons';

export default class EzConfigBase extends EzConfigButtonsBase {
    static outlineTotalWidth(block) {
        let outlineOffset = parseInt(block.getComputedStyle('outline-offset'), 10);
        const outlineWidth = parseInt(block.getComputedStyle('outline-width'), 10);

        if (isNaN(outlineOffset)) {
            // Edge does not support offset-offset yet
            // 1 comes from the stylesheet, see theme/alloyeditor/content.css
            outlineOffset = 1;
        }
        return outlineOffset + outlineWidth;
    }

    static isEmpty(block) {
        const nodes = [...block.$.childNodes];
        const count = nodes.length;
        const areAllTextNodesEmpty = !!count && nodes.every((node) => node.nodeName === '#text' && !node.data.replace(/\u200B/g, ''));
        const isOnlyBreakLine = count === 1 && block.$.childNodes.item(0).localName === 'br';

        return count === 0 || isOnlyBreakLine || areAllTextNodesEmpty;
    }

    static setPositionFor(block, editor, getTopPosition) {
        const blockRect = block.getClientRect();
        const outlineWidth = EzConfigBase.outlineTotalWidth(block);
        const empty = EzConfigBase.isEmpty(block);
        let positionReference = block;
        let left = 0;

        if (editor.widgets.getByElement(block)) {
            left = blockRect.left;
        } else {
            if (empty) {
                block.appendHtml('<span>&nbsp;</span>');
                positionReference = block.findOne('span');
            }

            const range = document.createRange();
            const scrollLeft = parseInt(block.$.scrollLeft, 10);
            range.selectNodeContents(positionReference.$);
            left = range.getBoundingClientRect().left + scrollLeft;

            if (empty) {
                positionReference.remove();
            }
        }

        const topPosition = getTopPosition(block, editor);

        const domElement = new CKEDITOR.dom.element(ReactDOM.findDOMNode(this));

        domElement.addClass('ae-toolbar-transition');
        domElement.setStyles({
            left: left - outlineWidth + 'px',
            top: topPosition + 'px',
        });

        return true;
    }

    static getTopPosition(block, editor) {
        const blockRect = block.getClientRect();
        const outlineWidth = EzConfigBase.outlineTotalWidth(block);
        const xy = this.getWidgetXYPoint(
            blockRect.left - outlineWidth,
            blockRect.top + block.getWindow().getScrollPosition().y - outlineWidth,
            CKEDITOR.SELECTION_BOTTOM_TO_TOP
        );

        return xy[1];
    }

    static getBlockElement(payload) {
        const editor = payload.editor.get('nativeEditor');
        const nativeEvent = payload.editorEvent.data.nativeEvent;
        const targetElement = nativeEvent ? new CKEDITOR.dom.element(payload.editorEvent.data.nativeEvent.target) : null;
        const isWidgetElement = targetElement ? editor.widgets.getByElement(targetElement) : false;
        const path = editor.elementPath();
        let block = path.block;

        if (isWidgetElement) {
            const inlineCustomTag = path.elements.find((element) => element.$.dataset.ezelement === 'eztemplateinline');

            block = inlineCustomTag || targetElement;
        }

        if (!block ) {
            block = path.lastElement;
        }

        if (block.is('li')) {
            block = block.getParent();
        }

        if (block.is('td') || block.is('th')) {
            for (let parent of block.getParents()) {
                if (parent.getName() === 'table') {
                    block = parent;
                    break;
                }
            }
        }

        return block;
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
        return 'ae-arrow-box ae-arrow-box-bottom ez-ae-arrow-box-left';
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
        const editor = payload.editor.get('nativeEditor');
        const block = EzConfigBase.getBlockElement(payload);

        return EzConfigBase.setPositionFor.call(this, block, editor, EzConfigBase.getTopPosition.bind(this));
    }
}
