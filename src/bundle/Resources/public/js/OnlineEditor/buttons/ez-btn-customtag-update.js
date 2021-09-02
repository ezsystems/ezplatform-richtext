import React, { Component } from 'react';
import PropTypes from 'prop-types';
import AlloyEditor from 'alloyeditor';
import EzWidgetButton from './base/ez-widgetbutton';

export default class EzBtnCustomTagUpdate extends EzWidgetButton {
    constructor(props) {
        super(props);

        this.widget = this.getWidget();

        props.editor.get('nativeEditor').lockSelection();

        this.state = {
            values: props.values,
        };
    }

    componentDidMount() {
        if (!Object.keys(this.attributes).length) {
            this.saveCustomTag();
        }
    }

    /**
     * Renders the text input.
     *
     * @method renderString
     * @param {Object} config
     * @param {String} attrName
     * @return {Object} The rendered text input.
     */
    renderString(config, attrName) {
        return (
            <div className="attribute__wrapper">
                <label className="attribute__label form-control-label">{config.label}</label>
                <input
                    type="text"
                    defaultValue={config.defaultValue}
                    required={config.required}
                    className="attribute__input form-control"
                    value={this.state.values[attrName].value}
                    onChange={this.updateValues.bind(this)}
                    data-attr-name={attrName}
                />
            </div>
        );
    }

    /**
     * Renders the checkbox input.
     *
     * @method renderCheckbox
     * @param {Object} config
     * @param {String} attrName
     * @return {Object} The rendered checkbox input.
     */
    renderCheckbox(config, attrName) {
        const isChecked = this.state.values[attrName].value;

        return (
            <div className="attribute__wrapper">
                <label className="attribute__label form-control-label">{config.label}</label>
                <div class="ez-ae-switcher" title="">
                    <label class={`ez-ae-switcher__label ${isChecked ? 'is-checked' : ''}`}>
                        <span class="ez-ae-switcher__indicator"></span>
                        <input
                            type="checkbox"
                            defaultValue={config.defaultValue}
                            required={config.required}
                            className="attribute__input form-control ez-ae-switcher__input"
                            checked={isChecked}
                            onChange={this.updateValues.bind(this)}
                            data-attr-name={attrName}
                        />
                    </label>
                </div>
            </div>
        );
    }

    /**
     * Renders the number input.
     *
     * @method renderNumber
     * @param {Object} config
     * @param {String} attrName
     * @return {Object} The rendered number input.
     */
    renderNumber(config, attrName) {
        return (
            <div className="attribute__wrapper">
                <label className="attribute__label form-control-label">{config.label}</label>
                <input
                    type="number"
                    defaultValue={config.defaultValue}
                    required={config.required}
                    className="attribute__input form-control"
                    value={this.state.values[attrName].value}
                    onChange={this.updateValues.bind(this)}
                    data-attr-name={attrName}
                />
            </div>
        );
    }

    /**
     * Renders the select.
     *
     * @method renderSelect
     * @param {Object} config
     * @param {String} attrName
     * @return {Object} The rendered select.
     */
    renderSelect(config, attrName) {
        return (
            <div className="attribute__wrapper">
                <label className="attribute__label form-control-label">{config.label}</label>
                <select
                    className="attribute__input form-control"
                    value={this.state.values[attrName].value}
                    onChange={this.updateValues.bind(this)}
                    data-attr-name={attrName}>
                    {config.choices.map((choice) => this.renderChoice(choice, config.choicesLabel[choice]))}
                </select>
            </div>
        );
    }

    /**
     * Renders the link.
     *
     * @method renderLink
     * @param {Object} config
     * @param {String} attrName
     * @return {Object} The rendered link.
     */
    renderLink(config, attrName) {
        // @todo provide dedicated support for link attribute type
        return this.renderString(config, attrName);
    }

    /**
     * Renders the option.
     *
     * @method renderChoice
     * @param {String} choice
     * @param {String} label
     * @return {Object} The rendered option.
     */
    renderChoice(choice, label) {
        return <option value={choice}>{label}</option>;
    }

    /**
     * Renders the attribute.
     *
     * @method renderAttribute
     * @param {Object} attribute
     * @return {Object} The rendered attribute.
     */
    renderAttribute(attribute) {
        const attributeConfig = this.attributes[attribute];
        const renderMethods = this.getAttributeRenderMethods();
        const methodName = renderMethods[attributeConfig.type];

        return (
            <div
                className={`ez-ae-custom-tag__attributes ez-ae-custom-tag__attributes--${attributeConfig.type} ez-ae-custom-tag__attributes--${attribute}`}>
                {this[methodName](attributeConfig, attribute)}
            </div>
        );
    }

    /**
     * Lifecycle. Renders the UI of the button.
     *
     * @method render
     * @return {Object} The content which should be rendered.
     */
    render() {
        const cancelLabel = Translator.trans(/*@Desc("Cancel")*/ 'custom_tag_update_btn.cancel_btn.label', {}, 'alloy_editor');
        const saveLabel = Translator.trans(/*@Desc("Save")*/ 'custom_tag_update_btn.save_btn.label', {}, 'alloy_editor');
        const attrs = Object.keys(this.attributes);
        const isValid = this.isValid();

        return (
            <div className={`ez-ae-custom-tag ez-ae-custom-tag--${this.customTagName}`}>
                <div className="ez-ae-custom-tag__header">{this.name}</div>
                <div className="ez-ae-custom-tag__attributes-list">{attrs.map(this.renderAttribute.bind(this))}</div>
                <div className="ez-ae-custom-tag__footer">
                    <button className="btn btn-primary ez-btn-ae" onClick={this.saveCustomTag.bind(this)} disabled={!isValid}>
                        {saveLabel}
                    </button>
                    <button className="btn btn-link ez-btn-ae ez-btn-ae--cancel" onClick={this.cancelCustomTagEdit.bind(this)}>
                        {cancelLabel}
                    </button>
                </div>
            </div>
        );
    }

    /**
     * Checks if values are valid.
     *
     * @method isValid
     * @return {Boolean} are values valid
     */
    isValid() {
        return Object.keys(this.attributes).every((attr) => {
            return this.attributes[attr].required ? !!this.state.values[attr].value : true;
        });
    }

    /**
     * Creates the custom tag in AlloyEditor.
     *
     * @method saveCustomTag
     */
    saveCustomTag() {
        const { createNewTag, editor } = this.props;

        editor.get('nativeEditor').unlockSelection(true);

        if (createNewTag) {
            this.execCommand();
        }

        const widget = this.getWidget() || this.widget;
        const configValues = Object.assign({}, this.state.values);

        widget.setFocused(true);
        widget.setName(this.customTagName);
        widget.setWidgetAttributes(this.createAttributes());
        widget.renderHeader();
        widget.clearConfig();

        Object.keys(this.attributes).forEach((key) => {
            widget.setConfig(key, configValues[key].value);
        });
    }

    /**
     * Cancels the custom tag editing in AlloyEditor.
     *
     * @method cancelCustomTagEdit
     */
    cancelCustomTagEdit() {
        const widget = this.getWidget() || this.widget;

        if (widget) {
            widget.setFocused(true);
        }

        this.props.cancelExclusive();
    }

    /**
     * Creates attributes.
     *
     * @method createAttributes
     * @return {String} the ezattributes
     */
    createAttributes() {
        return Object.keys(this.attributes).reduce(
            (total, attr) => `${total}<p>${this.attributes[attr].label}: ${this.state.values[attr].value}</p>`,
            ''
        );
    }

    /**
     * Update values.
     *
     * @method updateValues
     * @param {Object} event
     */
    updateValues(event) {
        const values = Object.assign({}, this.state.values);
        const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value;

        values[event.target.dataset.attrName].value = value;

        this.setState({
            values: values,
        });
    }

    /**
     * Gets the render method map.
     *
     * @method getAttributeRenderMethods
     * @return {Object} the render method map
     */
    getAttributeRenderMethods() {
        return {
            string: 'renderString',
            boolean: 'renderCheckbox',
            number: 'renderNumber',
            choice: 'renderSelect',
            link: 'renderLink',
        };
    }
}

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezBtnCustomTagUpdate = EzBtnCustomTagUpdate;

EzBtnCustomTagUpdate.defaultProps = {
    command: 'ezcustomtag',
    modifiesSelection: true,
};

EzBtnCustomTagUpdate.propTypes = {
    editor: PropTypes.object.isRequired,
    label: PropTypes.string.isRequired,
    tabIndex: PropTypes.number.isRequired,
    cancelExclusive: PropTypes.func.isRequired,
};
