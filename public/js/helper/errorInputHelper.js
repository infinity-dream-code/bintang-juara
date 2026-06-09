function clearErrorMessages(formId) {
    const form = document.querySelector(`#${formId}`);
    const errorElements = form.querySelectorAll('.invalid-feedback');
    const errorClass = form.querySelectorAll('.is-invalid');

    errorElements.forEach(element => element.textContent = '');
    errorClass.forEach(element => element.classList.remove('is-invalid'));
}

function processErrors(errors, form = null) {
    Object.entries(errors).forEach(([key, value]) => {
        let field;
        if(form){
            let currentForm = document.getElementById(form)
            if (currentForm){
                field = currentForm.querySelector(`[name="${key}"]`);
            }else{
                return;
            }
        }else{
            field = document.querySelector(`[name="${key}"]`);
        }
        const errorMessage = value[0];

        function applyInvalidClasses(element) {
            element.classList.add('is-invalid');
            let errorFeedback = element.nextElementSibling;

            if (!errorFeedback || !errorFeedback.classList.contains('invalid-feedback')) {
                errorFeedback = document.createElement('div');
                errorFeedback.className = 'invalid-feedback';
                errorFeedback.role = 'alert';
                errorFeedback.textContent = errorMessage;
                element.parentNode.insertBefore(errorFeedback, element.nextSibling);
            } else {
                errorFeedback.textContent = errorMessage;
            }
        }

        if (field) {
            if (field.classList.contains('select2-hidden-accessible')) {
                let nextField = field.nextSibling;
                if (nextField){
                    applyInvalidClasses(nextField);
                }
            } else if (field.parentNode.classList.contains('input-group')) {
                applyInvalidClasses(field.parentNode);
                field.classList.add('is-invalid');
            } else {
                applyInvalidClasses(field);
            }
        }

        if (key === 'password') {
            const confirmField = document.querySelector(`[name="${key}_confirmation"]`);
            if (confirmField) applyInvalidClasses(confirmField);
        }
    });
}
