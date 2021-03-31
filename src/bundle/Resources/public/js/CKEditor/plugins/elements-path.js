import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

class ElementsPath extends Plugin {
    constructor(props) {
        super(props);

        this.elementsPathWrapper = null;

        this.updatePath = this.updatePath.bind(this);
    }

    updatePath(element) {
        if (element.name === '$root') {
            return;
        }

        const pathItem = `<li class="ez-elements-path__item">${element.name}</li>`;
        const container = document.createElement('ul');

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

        this.elementsPathWrapper.append(listItemNode);
    }

    init() {
        this.elementsPathWrapper = this.editor.sourceElement.parentElement.querySelector('.ez-elements-path');

        this.editor.model.document.selection.on('change:range', () => {
            this.elementsPathWrapper.innerHTML = '';

            this.editor.model.document.selection
                .getFirstPosition()
                .getAncestors()
                .forEach(this.updatePath);
        });
    }
}

export default ElementsPath;
