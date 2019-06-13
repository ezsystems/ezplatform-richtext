import PropTypes from 'prop-types';
import EzBtnCustomTagEdit from './ez-btn-customtag-edit';

export default class EzBtnInlineCustomTagEdit extends EzBtnCustomTagEdit {
	getUpdateBtnName() {
		return `ezBtn${this.customTagName.charAt(0).toUpperCase() + this.customTagName.slice(1)}Update`;
	}
}

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezBtnInlineCustomTagEdit = EzBtnInlineCustomTagEdit;

EzBtnInlineCustomTagEdit.propTypes = {
	editor: PropTypes.object.isRequired,
	label: PropTypes.string.isRequired,
	tabIndex: PropTypes.number.isRequired,
};
