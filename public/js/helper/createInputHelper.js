//under development
//example

const modals = [
    { modalId: 'modal-create', formId: 'form-create', inputs: [
        {
            type:'text',
            name:'kode',
            placeholder:'Kode',
            id:'kode',
            hidden:false,
            readonly:false,
            required: true,
        },
        {
            type:'textarea',
            name:'ket',
            placeholder:'Keterangan',
            id:'ket',
            hidden:false,
            readonly:false,
            required: true,
        },
    ]},
    { modalId: 'modal-update', formId: 'form-update',inputs: [
        {
            type:'text',
            name:'kode',
            placeholder:'Kode',
            id:'update-kode',
            hidden:true,
            readonly:true,
            required: true,
        },
        {
            type:'text',
            name:'kode',
            placeholder:'Kode',
            id:'update-kode',
            hidden:false,
            readonly:true,
            required: true,
        },
        {
            type:'textarea',
            name:'ket',
            placeholder:'Keterangan',
            id:'update-ket',
            hidden:false,
            readonly:false,
            required: true,
        },
    ] },
    { modalId: 'modal-delete', formId: 'form-delete' },
];

function createInput(formId, inputLocation, inputProperties){
    if(!formId || !inputLocation || !inputProperties) return;

    const form = document.getElementById(formId);
    if (!form) return;

    const containerId = inputLocation.id ?? false;
    const containerClass = inputLocation.className ?? false;
    let inputContainer;
    if (containerId){
        inputContainer = form.querySelector(`#${containerId}`);
    }else if (containerClass){
        inputContainer = form.querySelector(`.${containerClass}`);
    }else{
        return;
    }

    if (!inputContainer) {
        console.log('inputs container not found');
        return;
    }
    
    let inputRequired = inputProperties.required ?? true;

    function generateInput(container){
        let input;
        if (inputProperties.type == "textarea"){
            input = document.createElement('textarea');
        }else{
            input = document.createElement('input');
            input.type = inputProperties.type ?? 'text';
        }

        input.name = inputProperties.name;
        input.id = inputProperties.id;
        input.placeholder = inputProperties.placeholder?? inputProperties.name ?? "";
        input.className = inputProperties.classList? `form-control ${inputProperties.classList}`:"form-control";
        input.required = inputRequired;
        input.readOnly = inputProperties.readOnly??false;
        input.hidden = inputProperties.hidden??false;
        input.disabled = inputProperties.disabled ?? false;

        container.appendChild(input);
    }
    
    function generateLabel(container = null){
        let label;
        let labelClass = "form-label";
        if (inputRequired) labelClass = labelClass + " required";
        console.log(labelClass);
        
        // label = document.createElement('label');
        // label.className = '';
    }

    let hiddenInput = inputProperties.hidden??false;

    if (hiddenInput){
        console.log('hidden')
        generateInput(inputContainer);
    }else{
        generateLabel();
        console.log('not hidden')
    }
}