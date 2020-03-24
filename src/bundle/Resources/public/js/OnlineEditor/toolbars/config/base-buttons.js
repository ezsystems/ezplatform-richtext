import AlloyEditor from 'alloyeditor';

export default class EzConfigButtonsBase {
    getButtons(config) {
        const toolbarConfig = window.eZ.richText.alloyEditor.toolbars[this.name];

        if (!toolbarConfig) {
            return [];
        }

        const buttons = [...toolbarConfig.buttons];
        const attributesEditIndex = buttons.indexOf('ezattributesedit');
        const stylesIndex = buttons.indexOf('ezstyles');

        if (attributesEditIndex > -1) {
            buttons[attributesEditIndex] = this.getEditAttributesButton(config);
        }

        if (stylesIndex > -1) {
            buttons[stylesIndex] = this.getStyles(config.customStyles);
        }

        return buttons;
    }

    getStyles(customStyles = []) {
        const headingLabel = Translator.trans(/*@Desc("Heading")*/ 'toolbar_config_base.heading_label', {}, 'alloy_editor');
        const paragraphLabel = Translator.trans(/*@Desc("Paragraph")*/ 'toolbar_config_base.paragraph_label', {}, 'alloy_editor');
        const formattedLabel = Translator.trans(/*@Desc("Formatted")*/ 'toolbar_config_base.formatted_label', {}, 'alloy_editor');

        return {
            name: 'styles',
            cfg: {
                showRemoveStylesItem: false,
                styles: [
                    { name: `${headingLabel} 1`, style: { element: 'h1' } },
                    { name: `${headingLabel} 2`, style: { element: 'h2' } },
                    { name: `${headingLabel} 3`, style: { element: 'h3' } },
                    { name: `${headingLabel} 4`, style: { element: 'h4' } },
                    { name: `${headingLabel} 5`, style: { element: 'h5' } },
                    { name: `${headingLabel} 6`, style: { element: 'h6' } },
                    { name: paragraphLabel, style: { element: 'p' } },
                    { name: formattedLabel, style: { element: 'pre' } },
                    ...customStyles,
                ],
            },
        };
    }

    getEditAttributesButton(config) {
        return config.attributes[this.name] || config.classes[this.name] ? `${this.name}edit` : '';
    }
}
