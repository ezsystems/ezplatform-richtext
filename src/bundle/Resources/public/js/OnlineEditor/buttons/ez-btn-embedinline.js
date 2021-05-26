import React, { Component } from 'react';
import PropTypes from 'prop-types';
import AlloyEditor from 'alloyeditor';
import EzBtnEmbed from './ez-btn-embed';

export default class EzBtnEmbedInline extends EzBtnEmbed {
    static get key() {
        return 'ezembedinline';
    }

    /**
     * Lifecycle. Renders the UI of the button.
     *
     * @method render
     * @return {Object} The content which should be rendered.
     */
    render() {
        const css = 'ae-button ibexa-btn-ae ibexa-btn-ae--embed-inline';
        const label = Translator.trans(/*@Desc("Embed")*/ 'embed_btn.label', {}, 'alloy_editor');

        return (
            <button className={css} onClick={this.chooseContent.bind(this)} tabIndex={this.props.tabIndex} title={label}>
                <svg className="ez-icon ibexa-btn-ae__icon">
                    <use xlinkHref={window.eZ.helpers.icon.getIconPath('embed')} />
                </svg>
            </button>
        );
    }
}

AlloyEditor.Buttons[EzBtnEmbedInline.key] = AlloyEditor.EzBtnEmbedInline = EzBtnEmbedInline;

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezBtnEmbedInline = EzBtnEmbedInline;

EzBtnEmbedInline.defaultProps = {
    command: 'ezembedinline',
    modifiesSelection: true,
    udwTitle: Translator.trans(/*@Desc("Select a Content item to embed")*/ 'embed_btn.udw.title', {}, 'alloy_editor'),
    udwContentDiscoveredMethod: 'addEmbed',
    udwConfigName: 'richtext_embed',
};
