// works well in input type=text
// add formattedNumber in input class for Currency format number
// add onlyNumber in input class for number only input (without currency formatting)
// not accepting negative value, decimals
// add max or min attribute in input for limiting value
// delete prosition broken, need fix

document.addEventListener('keypress', function (e) {
    const allowedClasses = ['formattedNumber', 'onlyNumber'];
    if (allowedClasses.some(cls => e.target.classList.contains(cls))) {
        const charCode = e.which || e.keyCode;
        if (charCode < 48 || charCode > 57) {
            e.preventDefault();
        }
    }
});

document.addEventListener('paste', function (e) {
    const allowedClasses = ['formattedNumber', 'onlyNumber'];
    if (allowedClasses.some(cls => e.target.classList.contains(cls))) {
        const inputElement = e.target;
        const oldElementValue = inputElement.value;
        if (inputElement.hasAttribute('readonly') || inputElement.disabled) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        const clipboardData = (e.clipboardData || window.clipboardData).getData('text');
        const sanitizedValue = clipboardData.replace(/[^0-9]/g, '');
        const sanitizedOldElementValue = oldElementValue.replace(/[^0-9]/g, '');
        if (sanitizedValue) {
            let parsedNumber = parseInt(sanitizedValue, 10);
            parsedNumber = parsedNumber + parseInt(sanitizedOldElementValue,10);

            if (inputElement.hasAttribute('max')) {
                const maxValue = parseInt(inputElement.getAttribute('max'), 10);
                if (!isNaN(maxValue) && parsedNumber > maxValue) {
                    parsedNumber = maxValue;
                }
            }
            if (inputElement.hasAttribute('min')) {
                const minValue = parseInt(inputElement.getAttribute('min'), 10);
                if (!isNaN(minValue) && parsedNumber < minValue) {
                    parsedNumber = minValue;
                }
            }
            if (inputElement.classList.contains('onlyNumber')){
                inputElement.value = parsedNumber;
            }else{
                inputElement.value = parsedNumber.toLocaleString('id-ID');
            }
        }
    }
});

document.addEventListener('input', function (e) {
    const allowedClasses = ['formattedNumber', 'onlyNumber'];
    if (allowedClasses.some(cls => e.target.classList.contains(cls))) {
        const inputElement = e.target;
        const formattedValue = inputElement.value;
        // const cursorPosition = inputElement.selectionStart;
        let parsedNumber = parseInt(formattedValue.replace(/\./g, ''), 10);
        if (!isNaN(parsedNumber)) {
            if (inputElement.hasAttribute('max')) {
                const maxValue = parseInt(inputElement.getAttribute('max'), 10);
                if (!isNaN(maxValue) && parsedNumber > maxValue) {
                    parsedNumber = maxValue;
                }
            }
            if (inputElement.hasAttribute('min')) {
                const minValue = parseInt(inputElement.getAttribute('min'), 10);
                if (!isNaN(minValue) && parsedNumber < minValue) {
                    parsedNumber = minValue;
                }
            }
            let formattedString;
            if (inputElement.classList.contains('formattedNumber')) {
                formattedString = parsedNumber.toLocaleString('id-ID');
            } else {
                formattedString = parsedNumber;
            }
            inputElement.value = formattedString;
            // const newCursorPosition = Math.max(0, cursorPosition + (formattedString.length - formattedValue.length));
            // inputElement.setSelectionRange(newCursorPosition, newCursorPosition);
        }
    }
});
