import Command from '@ckeditor/ckeditor5-core/src/command';

class IbexaLinkCommand extends Command {
    execute(linkData) {
        this.editor.model.change((writer) => {
            const ranges = this.editor.model.schema.getValidRanges(this.editor.model.document.selection.getRanges(), 'ibexaLinkHref');

            for (const range of ranges) {
                writer.setAttribute('ibexaLinkHref', linkData.href, range);
                writer.setAttribute('ibexaLinkTitle', linkData.title, range);
                writer.setAttribute('ibexaLinkTarget', linkData.target, range);
            }
        });
    }
}

export default IbexaLinkCommand;
