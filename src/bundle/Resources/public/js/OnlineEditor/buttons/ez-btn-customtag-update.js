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
            linkDetails: {},
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
        const selectContentLabel = Translator.trans(
            /*@Desc("Select content")*/ 'custom_tag.link.select_content_btn.label',
            {},
            'alloy_editor'
        );
        if (this.state.values[attrName].value) {
            this.loadLinkContentInfo(attrName);
        }
        const linkDetails = this.state.linkDetails[attrName];

        return (
            <div className="attribute__wrapper">
                <label className="attribute__label form-control-label">{config.label}</label>
                <input
                    type="text"
                    defaultValue={config.defaultValue}
                    required={config.required}
                    className="attribute__input form-control"
                    value={this.state.values[attrName].value}
                    onChange={this.updateLinkValues.bind(this)}
                    data-attr-name={attrName}
                />
                <div className="ez-custom-tag__link-controls">
                    {linkDetails
                        ? <a href={linkDetails.href} target="_blank" className="ez-custom-tag--link"
                             title={linkDetails.title}>{linkDetails.title}</a>
                        : ''
                    }
                    <button
                        className="ez-btn-ae btn btn-secondary"
                        onClick={this.selectContent.bind(this, attrName)}
                    >
                        {selectContentLabel}
                    </button>
                </div>
            </div>
        );
    }

    updateLinkValues(event) {
        this.updateValues(event);
        this.loadLinkContentInfo(event.target.dataset.attrName);
    }

    loadLinkContentInfo(attrName) {
        const inputValue = this.state.values[attrName].value;
        const linkDetails = this.state.linkDetails[attrName] || {};

        let filter;
        if (inputValue.startsWith('ezlocation://') && inputValue.length > 13) {
            const locationId = parseInt(inputValue.substring(13));
            if (!locationId) {
                this.clearLinkDetails(attrName);
                return;
            }
            if (linkDetails.locationId === locationId) {
                return;
            }
            linkDetails.locationId = locationId;
            filter = { LocationIdCriterion: locationId };
        } else if (inputValue.startsWith('ezcontent://') && inputValue.length > 14) {
            const contentId = parseInt(inputValue.substring(14));
            if (!contentId) {
                this.clearLinkDetails(attrName);
                return;
            }
            if (linkDetails.contentId === contentId) {
                return;
            }
            linkDetails.contentId = contentId;
            filter = { ContentIdCriterion: contentId };
        } else {
            this.clearLinkDetails(attrName);
            return;
        }

        const token = document.querySelector('meta[name="CSRF-Token"]').content;
        const siteaccess = document.querySelector('meta[name="SiteAccess"]').content;

        const body = JSON.stringify({
            ViewInput: {
                identifier: `custom-tag-link-info-by-id-${linkDetails.contentId || ''}-${linkDetails.locationId || ''}`,
                public: false,
                LocationQuery: {
                    Criteria: {},
                    FacetBuilders: {},
                    SortClauses: { LocationDepth: 'ascending' },
                    Filter: filter,
                    limit: 1,
                    offset: 0,
                },
            },
        });
        const request = new Request('/api/ezp/v2/views', {
            method: 'POST',
            headers: {
                Accept: 'application/vnd.ez.api.View+json; version=1.1',
                'Content-Type': 'application/vnd.ez.api.ViewInput+json; version=1.1',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Siteaccess': siteaccess,
                'X-CSRF-Token': token,
            },
            body,
            mode: 'same-origin',
            credentials: 'same-origin',
        });

        fetch(request)
            .then(window.eZ.helpers.request.getJsonFromResponse)
            .then((viewData) => {
                const resHits = viewData.View.Result.searchHits.searchHit;
                if (!resHits.length || !resHits[0].value) {
                    this.clearLinkDetails(attrName)
                    return;
                }

                this.setLinkDetails(attrName, resHits[0].value.Location);
            });
    }

    setLinkDetails(attrName, location) {
        const content = location.ContentInfo.Content;
        const linkDetails = Object.assign({}, this.state.linkDetails);
        linkDetails[attrName] = {
            title: content.TranslatedName || content.Name || '',
            href: Routing.generate('_ez_content_view', {
                contentId: content._id,
                locationId: location.id,
            }),
            contentId: content._id,
            locationId: location.id,
        };
        this.setState({ linkDetails });
    }

    clearLinkDetails(attrName) {
        if (this.state.linkDetails[attrName]) {
            const linkDetails = Object.assign({}, this.state.linkDetails);
            delete linkDetails[attrName];
            this.setState({ linkDetails });
        }
    }

    /**
     * Runs the Universal Discovery Widget so that the user can pick a Content.
     *
     * @method selectContent
     * @protected
     */
    selectContent(attrName) {
        const openUDW = () => {
            const config = JSON.parse(document.querySelector(`[data-udw-config-name="richtext_embed"]`).dataset.udwConfig);
            const title = Translator.trans(/*@Desc("Select content")*/ 'custom_tag.link.udw.title', {}, 'alloy_editor');
            const selectContent = eZ.richText.alloyEditor.callbacks.selectContent;
            const mergedConfig = Object.assign(
                {
                    onConfirm: this.udwOnConfirm.bind(this, attrName),
                    onCancel: this.udwOnCancel.bind(this),
                    title,
                    multiple: false,
                },
                config
            );

            if (typeof selectContent === 'function') {
                selectContent(mergedConfig);
            }
        };
        openUDW();

        this.disableUDWPropagation();
    }

    udwOnConfirm(attrName, items) {
        this.state.values[attrName].value = 'ezlocation://' + items[0].id;
        this.setLinkDetails(attrName, items[0]);

        this.enableUDWPropagation();
    }

    udwOnCancel() {
        ReactDOM.unmountComponentAtNode(document.querySelector('#react-udw'));
        this.enableUDWPropagation();
    }

    /**
     * Disable propagation to make sure attributes toolbar
     * not closed by alloyeditor outside click
     */
    disableUDWPropagation() {
        const container = document.querySelector('body');
        container.addEventListener('mousedown', this.doNotPropagate);
        container.addEventListener('keydown', this.doNotPropagate);
    }

    enableUDWPropagation() {
        const container = document.querySelector('body');
        container.removeEventListener('mousedown', this.doNotPropagate);
        container.removeEventListener('keydown', this.doNotPropagate);
    }

    doNotPropagate(event) {
        event.stopPropagation();
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
            <div className={`ez-ae-custom-tag__attributes ez-ae-custom-tag__attributes--${attributeConfig.type} ez-ae-custom-tag__attributes--${attribute}`}>
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
                <div className="ez-ae-custom-tag__header">
                    {this.name}
                </div>
                <div className="ez-ae-custom-tag__attributes-list">
                    {attrs.map(this.renderAttribute.bind(this))}
                </div>
                <div className="ez-ae-custom-tag__footer">
                    <button
                        className="ez-btn-ae btn ez-btn-ae--cancel"
                        onClick={this.props.cancelExclusive}
                    >
                        {cancelLabel}
                    </button>
                    <button
                        className="ez-btn-ae btn btn-primary"
                        onClick={this.saveCustomTag.bind(this)}
                        disabled={!isValid}>
                        {saveLabel}
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
