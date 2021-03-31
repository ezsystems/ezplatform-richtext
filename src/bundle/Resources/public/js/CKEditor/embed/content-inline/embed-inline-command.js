import Command from '@ckeditor/ckeditor5-core/src/command';

class EmbedContentInlineCommand extends Command {
    execute(contentData) {
        this.editor.model.change((writer) => {
            this.editor.model.insertContent(this.createEmbed(writer, contentData));
        });
    }

    createEmbed(writer, { contentId, contentName }) {
        return writer.createElement('embedInline', { contentId, contentName });
    }
}

export default EmbedContentInlineCommand;
