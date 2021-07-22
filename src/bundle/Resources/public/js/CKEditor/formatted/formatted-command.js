import Command from '@ckeditor/ckeditor5-core/src/command';

class IbexaFormattedCommand extends Command {
    execute() {
        const blocks = Array.from(this.editor.model.document.selection.getSelectedBlocks());

        this.editor.model.change((writer) => {
            for (const block of blocks) {
                writer.rename(block, 'formatted');
                this.editor.model.schema.removeDisallowedAttributes([block], writer);
            }

            blocks.reverse().forEach((currentBlock, i) => {
                const nextBlock = blocks[i + 1];

                if (currentBlock.previousSibling === nextBlock) {
                    writer.appendElement('softBreak', nextBlock);
                    writer.merge(writer.createPositionBefore(currentBlock));
                }
            });
        });
    }
}

export default IbexaFormattedCommand;
