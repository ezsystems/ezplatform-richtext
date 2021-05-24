import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import IbexaCustomTagsUI from './custom-tag-ui';
import IbexaCustomTagsEditing from './custom-tag-editing';

class IbexaCustomTags extends Plugin {
    static get requires() {
        const customTagsUi = Object.entries(window.eZ.richText.customTags).map(([name, config]) => {
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

        return [...customTagsUi, IbexaCustomTagsEditing];
    }
}

export default IbexaCustomTags;
