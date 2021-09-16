import View from '@ckeditor/ckeditor5-ui/src/view';
import IbexaButtonView from '../../common/button-view/button-view';

class IbexaCustomTagFormView extends View {
    constructor(props) {
        super(props);

        this.locale = props.locale;
    }

    setChildren(childrenData) {
        this.childrenData = childrenData;
    }

    setValues(values, label) {
        this.children = this.createFormChildren(this.childrenData.attributes, values, label);

        this.setTemplate({
            tag: 'div',
            attributes: {
                class: 'ibexa-custom-tag-attributes',
            },
            children: this.children,
        });
    }

    createFormChildren(attributes, values, label) {
        const buttonView = new IbexaButtonView(this.locale);

        buttonView.set({
            icon: window.eZ.helpers.icon.getIconPath('edit'),
        });

        buttonView.delegate('execute').to(this, 'edit-attributes');

        const children = [
            {
                tag: 'div',
                attributes: {
                    class: 'ibexa-custom-tag-attributes__header',
                },
                children: [
                    {
                        tag: 'div',
                        attributes: {
                            class: 'ibexa-custom-tag-attributes__header-title',
                        },
                        children: [label],
                    },
                    {
                        tag: 'div',
                        attributes: {
                            class: 'ibexa-custom-tag-attributes__header-actions',
                        },
                        children: [buttonView],
                    },
                ],
            },
        ];

        Object.entries(attributes).forEach(([name, config]) => {
            const value = values[name] === null || values[name] === '' ? '-' : values[name];

            children.push({
                tag: 'div',
                attributes: {
                    class: 'ibexa-custom-tag-attributes__item',
                },
                children: [
                    {
                        tag: 'div',
                        attributes: {
                            class: 'ibexa-custom-tag-attributes__item-label',
                        },
                        children: [config.label],
                    },
                    {
                        tag: 'div',
                        attributes: {
                            class: 'ibexa-custom-tag-attributes__item-value',
                        },
                        children: [value],
                    },
                ],
            });
        });

        return children;
    }
}

export default IbexaCustomTagFormView;
