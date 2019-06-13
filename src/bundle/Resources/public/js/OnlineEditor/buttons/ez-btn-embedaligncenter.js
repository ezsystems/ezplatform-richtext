import React, { Component } from 'react';
import PropTypes from 'prop-types';
import AlloyEditor from 'alloyeditor';
import EzEmbedAlign from './base/ez-embedalign';

export default class EzEmbedAlignCenter extends EzEmbedAlign {
	static get key() {
		return 'ezembedcenter';
	}
}

AlloyEditor.Buttons[EzEmbedAlignCenter.key] = AlloyEditor.EzEmbedAlignCenter = EzEmbedAlignCenter;

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezEmbedAlignCenter = EzEmbedAlignCenter;

EzEmbedAlignCenter.defaultProps = {
	alignment: 'center',
	iconName: 'image-center',
	cssClassSuffix: 'embed-center',
	label: Translator.trans(/*@Desc("Center")*/ 'embed_align_center_btn.label', {}, 'alloy_editor'),
};
