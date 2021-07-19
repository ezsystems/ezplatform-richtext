import Command from '@ckeditor/ckeditor5-core/src/command';

class IbexaMoveCommand extends Command {
    execute(moveData) {
        this.editor.model.change((writer) => {
            const parentElement = this.editor.model.document.selection.getFirstPosition().parent;
            const ancestors = parentElement.getAncestors();
            const elementToMove = ancestors[1] ?? parentElement;
            const elementRange = writer.createRangeOn(elementToMove);

            if (moveData.up && elementToMove.previousSibling) {
                writer.move(elementRange, elementToMove.previousSibling, 'before');
            } else if (!moveData.up && elementToMove.nextSibling) {
                writer.move(elementRange, elementToMove.nextSibling, 'after');
            }
        });
    }
}

export default IbexaMoveCommand;
