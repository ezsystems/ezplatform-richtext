import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';
import EzWidgetButton from './ez-widgetbutton';

export default class EzEmbedDiscoverContentButton extends EzWidgetButton {
    constructor(props) {
        super(props);

        this.confirmHandler = this.confirmHandler.bind(this);
        this.cancelHandler = this.cancelHandler.bind(this);
    }

    confirmHandler() {
        const { editor, udwContentDiscoveredMethod } = this.props;

        editor.get('nativeEditor').unlockSelection(true);

        this[udwContentDiscoveredMethod].apply(this, arguments);
    }

    cancelHandler() {
        this.props.editor.get('nativeEditor').unlockSelection(true);
    }

    /**
     * Triggers the UDW to choose the content to embed.
     *
     * @method chooseContent
     */
    chooseContent() {
        const { udwConfigName, udwTitle, editor } = this.props;
        const languageCode = document.querySelector('meta[name="LanguageCode"]').content;
        const config = JSON.parse(document.querySelector(`[data-udw-config-name="${udwConfigName}"]`).dataset.udwConfig);
        const selectContent = eZ.richText.alloyEditor.callbacks.selectContent;
        const mergedConfig = Object.assign(
            {
                onConfirm: this.confirmHandler,
                onCancel: this.cancelHandler,
                title: udwTitle,
                multiple: false,
            },
            config,
            {
                contentOnTheFly: {
                    allowedLanguages: [languageCode],
                },
            }
        );

        editor.get('nativeEditor').lockSelection();

        if (typeof selectContent === 'function') {
            selectContent(mergedConfig);
        }
    }

    /**
     * Sets the href of the ezembed widget based on the given content info
     *
     * @method setContentInfo
     * @param {eZ.ContentInfo} contentInfo
     */
    setContentInfo(contentId) {
        const embedWidget = this.getWidget();

        embedWidget.setHref('ezcontent://' + contentId);
        embedWidget.focus();
    }
}
