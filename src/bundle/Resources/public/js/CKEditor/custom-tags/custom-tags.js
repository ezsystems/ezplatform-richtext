import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaCustomTagsUI from './block-custom-tag/custom-tag-ui';
import IbexaInlineCustomTagsUI from './inline-custom-tag/inline-custom-tag-ui';
import IbexaCustomTagsEditing from './block-custom-tag/custom-tag-editing';
import IbexaInlineCustomTagsEditing from './inline-custom-tag/inline-custom-tag-editing';

class IbexaCustomTags extends Plugin {
    static get requires() {
        const blockCustomTags = Object.entries(window.eZ.richText.customTags).filter(([name, config]) => !config.isInline);
        const inlineCustomTags = Object.entries(window.eZ.richText.customTags).filter(([name, config]) => config.isInline);
        const inlineCustomTagsUi = inlineCustomTags.map(([name, config]) => {
            return class InlineCustomTagUI extends IbexaInlineCustomTagsUI {
                constructor(props) {
                    super(props);

                    this.componentName = name;
                    this.config = config;

                    this.formView.setChildren({
                        attributes: this.config.attributes,
                    });
                }
            };
        });
        const blockCustomTagsUi = blockCustomTags.map(([name, config]) => {
            return class CustomTagUI extends IbexaCustomTagsUI {
                constructor(props) {
                    super(props);

                    this.componentName = name;
                    this.config = config;

                    this.formView.setChildren({
                        attributes: this.config.attributes,
                    });
                }
            };
        });

        return [...blockCustomTagsUi, ...inlineCustomTagsUi, IbexaCustomTagsEditing, IbexaInlineCustomTagsEditing];
    }
}

export default IbexaCustomTags;
