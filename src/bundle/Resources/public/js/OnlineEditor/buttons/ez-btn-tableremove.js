import React, { Component } from 'react';
import PropTypes from 'prop-types';
import AlloyEditor from 'alloyeditor';

export default class EzBtnTableRemove extends AlloyEditor.ButtonTableRemove {
    static get key() {
        return 'eztableremove';
    }

    render() {
        return (
            <button
                aria-label={AlloyEditor.Strings.deleteTable}
                className="ae-button ez-btn-ae"
                data-type="button-table-remove"
                onClick={() => {
                    this._removeTable();
                    this.fireCustomUpdateEvent();
                }}
                tabIndex={this.props.tabIndex}
                title={AlloyEditor.Strings.deleteTable}>
                <svg className="ez-icon ez-btn-ae__icon">
                    <use xlinkHref={window.eZ.helpers.icon.getIconPath('trash')} />
                </svg>
            </button>
        );
    }

    fireCustomUpdateEvent() {
        const nativeEditor = this.props.editor.get('nativeEditor');

        nativeEditor.fire('customUpdate');
    }
}

AlloyEditor.Buttons[EzBtnTableRemove.key] = AlloyEditor.EzBtnTableRemove = EzBtnTableRemove;

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezBtnTableRemove = EzBtnTableRemove;
