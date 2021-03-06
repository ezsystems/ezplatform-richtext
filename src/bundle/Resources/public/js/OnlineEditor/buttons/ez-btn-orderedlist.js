import React, { Component } from 'react';
import PropTypes from 'prop-types';
import AlloyEditor from 'alloyeditor';
import EzButton from './base/ez-button';

export default class EzBtnOrderedList extends EzButton {
    static get key() {
        return 'ezorderedlist';
    }

    /**
     * Executes the eZAppendContent command to add an unordered list containing
     * an empty list item.
     *
     * @method addList
     */
    addList() {
        this.execCommand({
            tagName: 'ol',
            content: '<li></li>',
            focusElement: 'li',
        });
    }

    /**
     * Lifecycle. Renders the UI of the button.
     *
     * @method render
     * @return {Object} The content which should be rendered.
     */
    render() {
        const label = Translator.trans(/*@Desc("List")*/ 'ordered_list_btn.label', {}, 'alloy_editor');
        const css = 'ae-button ibexa-btn-ae ibexa-btn-ae--ordered-list ' + this.getStateClasses();

        return (
            <button className={css} onClick={this.addList.bind(this)} tabIndex={this.props.tabIndex} title={label}>
                <svg className="ibexa-icon ibexa-btn-ae__icon">
                    <use xlinkHref={window.eZ.helpers.icon.getIconPath('list-numbered')} />
                </svg>
            </button>
        );
    }
}

AlloyEditor.Buttons[EzBtnOrderedList.key] = AlloyEditor.EzBtnOrderedList = EzBtnOrderedList;

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezBtnOrderedList = EzBtnOrderedList;

EzBtnOrderedList.propTypes = {
    command: PropTypes.string,
    modifiesSelection: PropTypes.bool,
};

EzBtnOrderedList.defaultProps = {
    command: 'eZAddContent',
    modifiesSelection: true,
};
