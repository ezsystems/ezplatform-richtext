import React, { Component } from 'react';
import PropTypes from 'prop-types';
import AlloyEditor from 'alloyeditor';

export default class EzBtnAnchor extends Component {
    constructor(props) {
        super(props);

        this.getStateClasses = AlloyEditor.ButtonStateClasses.getStateClasses;
    }

    static get key() {
        return 'ezanchor';
    }

    /**
     * Lifecycle. Renders the UI of the button.
     *
     * @method render
     * @return {Object} The content which should be rendered.
     */
    render() {
        if (this.props.renderExclusive) {
            return <AlloyEditor.EzBtnAnchorEdit {...this.props} />;
        }

        const cssClass = `ae-button ibexa-btn-ae--anchor ibexa-btn-ae ${this.getStateClasses()}`;
        const label = Translator.trans(/*@Desc("Anchor")*/ 'anchor_btn.label', {}, 'alloy_editor');

        return (
            <button
                aria-pressed={cssClass.indexOf('pressed') !== -1}
                className={cssClass}
                onClick={this.props.requestExclusive}
                tabIndex={this.props.tabIndex}
                title={label}>
                <svg className="ibexa-icon ibexa-btn-ae__icon">
                    <use xlinkHref={window.eZ.helpers.icon.getIconPath('link-anchor')} />
                </svg>
            </button>
        );
    }
}

AlloyEditor.Buttons[EzBtnAnchor.key] = AlloyEditor.EzBtnAnchor = EzBtnAnchor;

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezBtnAnchor = EzBtnAnchor;
