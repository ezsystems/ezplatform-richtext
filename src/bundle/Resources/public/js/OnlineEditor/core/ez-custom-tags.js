(function(global, doc, eZ, AlloyEditor) {
    Object.entries(eZ.richText.customTags).forEach(([customTag, tagConfig]) => {
        const isInline = tagConfig.isInline;
        const componentClassName = `ezBtn${customTag.charAt(0).toUpperCase() + customTag.slice(1)}`;
        const editComponentClassName = `${componentClassName}Edit`;
        const updateComponentClassName = `${componentClassName}Update`;
        const buttonCustomTagBaseClass = isInline ? eZ.ezAlloyEditor.ezBtnInlineCustomTag : eZ.ezAlloyEditor.ezBtnCustomTag;
        const buttonCustomTagEditBaseClass = isInline ? eZ.ezAlloyEditor.ezBtnInlineCustomTagEdit : eZ.ezAlloyEditor.ezBtnCustomTagEdit;
        const buttonCustomTagUpdateBaseClass = isInline
            ? eZ.ezAlloyEditor.ezBtnInlineCustomTagUpdate
            : eZ.ezAlloyEditor.ezBtnCustomTagUpdate;

        class ButtonCustomTag extends buttonCustomTagBaseClass {
            constructor(props) {
                super(props);

                const values = {};

                if (tagConfig.attributes) {
                    Object.entries(tagConfig.attributes).forEach(([attr, value]) => {
                        values[attr] = {
                            value: value.defaultValue,
                        };
                    });
                }

                this.label = tagConfig.label;
                this.icon = tagConfig.icon || window.eZ.helpers.icon.getIconPath('tag');
                this.customTagName = customTag;
                this.values = values;
            }

            static get key() {
                return customTag;
            }
        }

        class ButtonCustomTagEdit extends buttonCustomTagEditBaseClass {
            constructor(props) {
                super(props);

                this.customTagName = customTag;
                this.attributes = tagConfig.attributes || {};
            }

            static get key() {
                return `${customTag}edit`;
            }
        }

        class ButtonCustomTagUpdate extends buttonCustomTagUpdateBaseClass {
            constructor(props) {
                super(props);

                this.customTagName = customTag;
                this.attributes = tagConfig.attributes || {};
                this.name = tagConfig.label;
            }

            static get key() {
                return `${customTag}update`;
            }
        }

        AlloyEditor.Buttons[ButtonCustomTag.key] = AlloyEditor[componentClassName] = ButtonCustomTag;
        AlloyEditor.Buttons[ButtonCustomTagEdit.key] = AlloyEditor[editComponentClassName] = ButtonCustomTagEdit;
        AlloyEditor.Buttons[ButtonCustomTagUpdate.key] = AlloyEditor[updateComponentClassName] = ButtonCustomTagUpdate;
    });
})(window, window.document, window.eZ, window.AlloyEditor);
