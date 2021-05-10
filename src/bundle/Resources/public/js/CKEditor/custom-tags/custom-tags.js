import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

import CustomTagsUI from './custom-tag-ui';
import CustomTagsEditing from './custom-tag-editing';

class CustomTags extends Plugin {
    static get requires() {
        const customTagsUi = Object.entries(window.eZ.richText.customTags).map(([name, config]) => {
            return class CustomTagUI extends CustomTagsUI {
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

        return [...customTagsUi, CustomTagsEditing];
    }
}

export default CustomTags;
