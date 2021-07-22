import Command from '@ckeditor/ckeditor5-core/src/command';

class IbexaEmbedImageCommand extends Command {
    execute(contentData) {
        this.editor.model.change((writer) => {
            writer.setSelection(this.editor.model.document.selection.getFirstPosition().parent, 'end');

            this.editor.model.insertContent(this.createEmbed(writer, contentData));
        });
    }

    createEmbed(writer, { contentId, size }) {
        return writer.createElement('embedImage', { contentId, size });
    }
}

export default IbexaEmbedImageCommand;
