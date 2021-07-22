import Command from '@ckeditor/ckeditor5-core/src/command';

class IbexaInlineCustomTagCommand extends Command {
    execute(customTagData) {
        this.editor.model.change((writer) => {
            const inlineCustomTag = this.createCustomTag(writer, customTagData);

            this.editor.model.insertContent(inlineCustomTag);

            writer.setSelection(inlineCustomTag, 'on');
        });
    }

    createCustomTag(writer, { customTagName, values }) {
        const inlineCustomTag = writer.createElement('inlineCustomTag', { customTagName, values });
        const inlineCustomTagContent = writer.createElement('inlineCustomTagContent');
        const range = this.editor.model.document.selection.getFirstRange().clone();

        writer.append(inlineCustomTagContent, inlineCustomTag);

        for (const item of range.getItems()) {
            writer.append(item.data, inlineCustomTagContent);
        }

        return inlineCustomTag;
    }
}

export default IbexaInlineCustomTagCommand;
