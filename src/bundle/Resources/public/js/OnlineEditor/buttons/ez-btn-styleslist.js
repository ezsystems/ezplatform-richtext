import React from 'react';
import AlloyEditor from 'alloyeditor';
import EzButtonDropdown from './ez-btn-dropdown';

export default class EzButtonStylesList extends AlloyEditor.ButtonStylesList {

    render() {
        var e;
        return this.props.showRemoveStylesItem && (e = React.createElement(AlloyEditor.ButtonStylesListItemRemove, {
            editor: this.props.editor,
            onDismiss: this.props.toggleDropdown
        })), React.createElement(EzButtonDropdown, this.props, e, React.createElement(AlloyEditor.ButtonsStylesListHeader, {
            name: AlloyEditor.Strings.blockStyles,
            styles: this._blockStyles
        }), this._renderStylesItems(this._blockStyles), React.createElement(AlloyEditor.ButtonsStylesListHeader, {
            name: AlloyEditor.Strings.inlineStyles,
            styles: this._inlineStyles
        }), this._renderStylesItems(this._inlineStyles), React.createElement(AlloyEditor.ButtonsStylesListHeader, {
            name: AlloyEditor.Strings.objectStyles,
            styles: this._objectStyles
        }), this._renderStylesItems(this._objectStyles))
    }
}

AlloyEditor.ButtonStylesList = AlloyEditor.EzButtonStylesList = EzButtonStylesList;

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.EzButtonStylesList = EzButtonStylesList;
