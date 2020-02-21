import React from 'react';
import AlloyEditor from 'alloyeditor';

export default class EzBtnDropdown extends AlloyEditor.ButtonDropdown {
    render() {
        return React.createElement("div", {
            className: "ae-dropdown ae-arrow-box ae-arrow-box-top-left",
            onKeyDown: this.handleKey,
            tabIndex: "0",
        }, React.createElement("ul", {className: "ae-listbox", role: "listbox"}, this.props.children))
    }
}

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.EzBtnDropdown = EzBtnDropdown;
