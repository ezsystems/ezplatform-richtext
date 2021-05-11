import InputNumberView from './input-number-view';

export function createLabeledInputNumber(labeledFieldView, viewUid, statusUid) {
    const inputView = new InputNumberView(labeledFieldView.locale);

    inputView.set({
        id: viewUid,
        ariaDescribedById: statusUid,
    });

    inputView.bind('isReadOnly').to(labeledFieldView, 'isEnabled', (value) => !value);
    inputView.bind('hasError').to(labeledFieldView, 'errorText', (value) => !!value);

    inputView.on('input', () => {
        labeledFieldView.errorText = null;
    });

    labeledFieldView.bind('isEmpty', 'isFocused', 'placeholder').to(inputView);

    return inputView;
}
