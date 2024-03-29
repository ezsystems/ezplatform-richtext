import React, { Component } from 'react';
import PropTypes from 'prop-types';
import AlloyEditor from 'alloyeditor';
import EzWidgetButton from './base/ez-widgetbutton';

export default class EzBtnAttributesEdit extends EzWidgetButton {
    constructor(props) {
        super(props);

        this.setDefaultAttributesMethods = {
            tr: this.setDefaultAttributesOnTableRows,
            td: this.setDefaultAttributesOnTableCells,
            li: this.setDefaultAttributesOnListItems,
        };

        this.setDefaultClassesMethods = {
            tr: this.setDefaultClassesOnTableRows,
            td: this.setDefaultClassesOnTableCells,
            li: this.setDefaultClassesOnListItems,
        };

        this.nativeAttributes = window.eZ.richText.alloyEditor.nativeAttributes;
    }

    componentDidMount() {
        const block = this.findSelectedBlock();

        if (!block.$.getAttribute('data-ez-node-initialized')) {
            this.removeClasses(block);
            this.removeAttributes(block);
        }

        this.setDefaultClasses(block);
        this.setDefaultAttributes(block);

        block.$.setAttribute('data-ez-node-initialized', true);

        this.beforeCommandExecHandler = this.props.editor
            .get('nativeEditor')
            .on('beforeCommandExec', this.toggleNodeInitialized.bind(this, block, false));
        this.afterCommandExecHandler = this.props.editor.get('nativeEditor').on('afterCommandExec', (event) => {
            let add = true;

            if (event.data.name === 'removeFormat') {
                this.toggleNodeInitialized(block, add);

                add = false;
                this.block = null;
            }

            this.toggleNodeInitialized(this.findSelectedBlock(), add);
        });
    }

    toggleNodeInitialized(block, add) {
        const methodName = add ? 'setAttribute' : 'removeAttribute';

        block.$[methodName]('data-ez-node-initialized', true);
    }

    componentDidUpdate() {
        this.block = null;

        const block = this.findSelectedBlock();

        if (!block.$.getAttribute('data-ez-node-initialized')) {
            this.removeClasses(block);
            this.removeAttributes(block);
            this.setDefaultClasses(block);
            this.setDefaultAttributes(block);

            block.$.setAttribute('data-ez-node-initialized', true);
        }
    }

    componentWillUnmount() {
        this.block = null;

        const block = this.findSelectedBlock();

        this.beforeCommandExecHandler.removeListener();
        this.afterCommandExecHandler.removeListener();

        if (!block.$.getAttribute('data-ez-node-initialized')) {
            this.removeClasses(block);
            this.removeAttributes(block);

            block.$.setAttribute('data-ez-node-initialized', true);
        }
    }

    removeClasses(block) {
        const classes = [...block.$.classList];
        const classesToRemain = ['is-block-focused', 'ez-embed-type-image', 'is-linked'];

        classes.forEach((className) => {
            if (!classesToRemain.includes(className)) {
                block.$.classList.remove(className);
            }
        });
    }

    removeAttributes(block) {
        Object.values(block.$.attributes).forEach((attribute) => {
            if (attribute.name.startsWith('data-ezattribute')) {
                block.removeAttribute(attribute.name);
            }
        });
    }

    setDefaultClasses(block) {
        if (!Object.keys(this.classes).length || block.$.classList.contains('ez-classes-added') || !this.classes.defaultValue) {
            return;
        }

        const defaultValue = this.classes.defaultValue.split(',');
        const setDefaultClassesMethod = this.setDefaultClassesMethods[this.toolbarName]
            ? this.setDefaultClassesMethods[this.toolbarName]
            : this.setDefaultClassesOnBlock;

        setDefaultClassesMethod(block, defaultValue);
    }

    setDefaultClassesOnBlock(block, value) {
        block.$.classList.add(...value);
    }

    setDefaultClassesOnTableRows(block, value) {
        const rows = block.$.closest('table').querySelectorAll('tr');

        rows.forEach((row) => row.classList.add(...value));
    }

    setDefaultClassesOnTableCells(block, value) {
        const cells = block.$.closest('table').querySelectorAll('td');

        cells.forEach((cell) => cell.classList.add(...value));
    }

    setDefaultClassesOnListItems(block, value) {
        const list = block.$.closest('ul') || block.$.closest('ol');
        const listItems = list.querySelectorAll('li');

        listItems.forEach((listItem) => listItem.classList.add(...value));
    }

    setDefaultAttributes(block) {
        Object.entries(this.attributes).forEach(([attributeName, config]) => {
            const attributeValue = this.getAttributeValue(block, attributeName, config);

            if (attributeValue !== null) {
                return;
            }

            const defaultValue = config.defaultValue;

            if (defaultValue !== undefined && defaultValue !== null) {
                const setDefaultAttributesMethod = this.setDefaultAttributesMethods[this.toolbarName]
                    ? this.setDefaultAttributesMethods[this.toolbarName]
                    : this.setDefaultAttributesOnBlock;

                setDefaultAttributesMethod.bind(this)(block, attributeName, defaultValue);
            }
        });
    }

    setDefaultAttributesOnBlock(block, attributeName, value) {
        this.setAttributeValue(block, attributeName, value);
    }

    setDefaultAttributesOnTableRows(block, attributeName, value) {
        const rows = block.$.closest('table').querySelectorAll('tr');

        rows.forEach((row) => this.setAttributeValue(row, attributeName, value));
    }

    setDefaultAttributesOnTableCells(block, attributeName, value) {
        const cells = block.$.closest('table').querySelectorAll('td');

        cells.forEach((cell) => this.setAttributeValue(cell, attributeName, value));
    }

    setDefaultAttributesOnListItems(block, attributeName, value) {
        const list = block.$.closest('ul') || block.$.closest('ol');
        const listItems = list.querySelectorAll('li');

        listItems.forEach((listItem) => this.setAttributeValue(listItem, attributeName, value));
    }

    getAttributesValues() {
        return Object.entries(this.attributes).reduce((total, [attributeName, config]) => {
            const block = this.findSelectedBlock();
            const value = this.getAttributeValue(block, attributeName, config);

            total[attributeName] = { value };

            return total;
        }, {});
    }

    getAttributeValue(element, name, config) {
        const nativeAttributes = this.nativeAttributes[element.getName()] || [];

        if (nativeAttributes.includes(name)) {
            return element.getAttribute(name);
        }

        let value = element.getAttribute(`data-ezattribute-${name}`);
        const isValueDefined = value !== null;
        const defaultValue = config.defaultValue;

        if (config.type === 'choice' && !isValueDefined && !config.multiple) {
            value = config.choices[0];
        }

        if (!isValueDefined && defaultValue !== undefined && defaultValue !== null) {
            value = defaultValue;
        }

        if (config.type === 'boolean' && isValueDefined) {
            value = value === 'true';
        }

        return value;
    }

    setAttributeValue(element, name, value) {
        const nativeAttributes = this.nativeAttributes[element.getName()] || [];

        if (!nativeAttributes.includes(name)) {
            name = `data-ezattribute-${name}`;
        }

        element.setAttribute(name, value);
    }

    getClassesValue() {
        const block = this.findSelectedBlock();
        let value = block.$.classList.value
            .split(' ')
            .filter((className) => Array.isArray(this.classes.choices) && this.classes.choices.includes(className))
            .join();

        if (!value && !this.classes.multiple && Array.isArray(this.classes.choices)) {
            value = this.classes.choices[0];
        }

        return value;
    }

    getUpdateBtnName() {
        return `ezBtn${this.toolbarName.charAt(0).toUpperCase() + this.toolbarName.slice(1)}Update`;
    }

    /**
     * Lifecycle. Renders the UI of the button.
     *
     * @method render
     * @return {Object} The content which should be rendered.
     */
    render() {
        if (this.props.renderExclusive) {
            const buttonName = this.getUpdateBtnName();
            const ButtonComponent = AlloyEditor[buttonName];

            return <ButtonComponent attributesValues={this.getAttributesValues()} classesValue={this.getClassesValue()} {...this.props} />;
        }

        const css = `ae-button ez-btn-ae ez-btn-ae--${this.toolbarName}-edit`;

        return (
            <button className={css} onClick={this.props.requestExclusive} tabIndex={this.props.tabIndex}>
                <svg className="ez-icon ez-btn-ae__icon">
                    <use xlinkHref={window.eZ.helpers.icon.getIconPath('edit')} />
                </svg>
            </button>
        );
    }
}

const eZ = (window.eZ = window.eZ || {});

eZ.ezAlloyEditor = eZ.ezAlloyEditor || {};
eZ.ezAlloyEditor.ezBtnAttributesEdit = EzBtnAttributesEdit;

EzBtnAttributesEdit.propTypes = {
    editor: PropTypes.object.isRequired,
    label: PropTypes.string.isRequired,
    tabIndex: PropTypes.number.isRequired,
};
