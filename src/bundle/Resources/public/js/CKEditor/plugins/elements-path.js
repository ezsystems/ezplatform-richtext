import Plugin from '../../../../../../../../ezplatform-admin-ui-assets/Resources/public/vendors/@ckeditor/ckeditor5-core/src/plugin';

class ElementsPath extends Plugin {
    constructor(props) {
        super(props);

        this.elementsPathWrapper = null;

        this.updatePath = this.updatePath.bind(this);
        this.getElementsPathWrapper = this.getElementsPathWrapper.bind(this);
    }

    getElementsPathWrapper() {
        if (!this.elementsPathWrapper) {
            this.elementsPathWrapper = this.editor.sourceElement.parentElement.querySelector('.ez-elements-path');
        }

        return this.elementsPathWrapper;
    }

    updatePath(element) {
        if (element.name === '$root') {
            return;
        }

        const pathItem = `<li class="ez-elements-path__item">${element.name}</li>`;
        const container = document.createElement('ul');
        const elementsPathWrapper = this.getElementsPathWrapper();

        container.insertAdjacentHTML('beforeend', pathItem);

        const listItemNode = container.querySelector('li');

        listItemNode.addEventListener(
            'click',
            () => {
                this.editor.model.change((writer) => writer.setSelection(element, 'in'));
                this.editor.focus();
            },
            false
        );

        elementsPathWrapper.append(listItemNode);
    }

    init() {
        this.editor.model.document.selection.on('change:range', () => {
            const elementsPathWrapper = this.getElementsPathWrapper();

            elementsPathWrapper.innerHTML = '';

            this.editor.model.document.selection
                .getFirstPosition()
                .getAncestors()
                .forEach(this.updatePath);
        });
    }
}

export default ElementsPath;
