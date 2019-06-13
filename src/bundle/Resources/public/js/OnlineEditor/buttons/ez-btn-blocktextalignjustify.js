import React, { Component } from 'react';
import PropTypes from 'prop-types';
import AlloyEditor from 'alloyeditor';
import EzBlockTextAlign from './base/ez-blocktextalign';

export default class EzBtnBlockTextAlignJustify extends EzBlockTextAlign {
    static get key() {
        return 'ezblocktextalignjustify';
    }
}

AlloyEditor.Buttons[EzBtnBlockTextAlignJustify.key] = AlloyEditor.EzBtnBlockTextAlignJustify = EzBtnBlockTextAlignJustify;

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezBtnBlockTextAlignJustify = EzBtnBlockTextAlignJustify;

EzBtnBlockTextAlignJustify.defaultProps = {
    textAlign: 'justify',
    iconName: 'align-justify',
    cssClassSuffix: 'align-justify',
    label: Translator.trans(/*@Desc("Justify")*/ 'blocktext_align_justify_btn.label', {}, 'alloy_editor'),
};
