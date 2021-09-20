import Plugin from '@ckeditor/ckeditor5-core/src/plugin';

const TEXT_NODE = 3;

class IbexaCharacterCounter extends Plugin {
    splitIntoWords(text) {
        return text.split(' ').filter((word) => word.trim());
    }

    sanitize(text) {
        return text.replace(/[\u200B-\u200D\uFEFF]/g, '');
    }

    iterateThroughChildNodes(node, callback) {
        if (typeof node.getAttribute === 'function' && node.getAttribute('data-ezelement') === 'ezconfig') {
            return;
        }

        callback(node);
        node = node.firstChild;

        while (node) {
            this.iterateThroughChildNodes(node, callback);
            node = node.nextSibling;
        }
    }

    getTextNodeValues(node) {
        let values = [];
        const pushValue = (node) => {
            if (node.nodeType === TEXT_NODE) {
                const nodeValue = this.sanitize(node.nodeValue);

                values = values.concat(this.splitIntoWords(nodeValue));
            }
        };

        this.iterateThroughChildNodes(node, pushValue);

        return values;
    }

    countWordsCharacters(container, editorHtml) {
        const counterWrapper = container.parentElement.querySelector('.ibexa-character-counter');

        if (counterWrapper) {
            const wordWrapper = counterWrapper.querySelector('.ibexa-character-counter__word-count');
            const charactersWrapper = counterWrapper.querySelector('.ibexa-character-counter__character-count');
            const words = this.getTextNodeValues(editorHtml);

            wordWrapper.innerText = words.length;
            charactersWrapper.innerText = words.join(' ').length;
        }
    }

    init() {
        this.editor.model.document.on('change:data', () => {
            const data = this.editor.getData();
            const div = document.createElement('div');
            const documentFragment = document.createDocumentFragment();

            div.innerHTML = data;
            documentFragment.appendChild(div);

            this.countWordsCharacters(this.editor.sourceElement, documentFragment);
        });
    }
}

export default IbexaCharacterCounter;
