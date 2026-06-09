function clearErrorMessages(formId) {
    const form = document.querySelector(`#${formId}`);
    const errorElements = form.querySelectorAll('.invalid-feedback');
    const errorClass = form.querySelectorAll('.is-invalid');

    errorElements.forEach(element => element.textContent = '');
    errorClass.forEach(element => element.classList.remove('is-invalid'));
}

function clearSelect(id) {
    var $select = $(`#${id}`);
    $select.find('option').remove();
    console.log($select)
}
