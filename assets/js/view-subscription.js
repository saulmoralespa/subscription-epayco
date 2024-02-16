var $ = jQuery;
const containCard = $('#card-epayco-suscribir');
const formCard = containCard.find('form');
ePayco.setPublicKey(subscription_epayco.publicKey);
$('#subscription-epayco-button-card-update').click(function(e){
    $(this).find('#card-epayco-suscribir').show();
    containCard.show();
    loadCard();
    $(this).hide();
});


let buttonCancel = document.querySelector('.button.cancel');

if (buttonCancel){
    buttonCancel.addEventListener('click', function (e){
        e.preventDefault();

        swal.fire({
            title: "Confirmación de cancelación",
            text: "¿Estás seguro de que deseas continuar?",
            icon: "warning",
            showCancelButton: true,
            cancelButtonText: "Cancelar",
            confirmButtonText: "Confirmar"
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = e.target.getAttribute('href');
            }
        });
    });
}
function subscriptionEpaycoFormHandler(e){
    e.preventDefault();
    let number_card = formCard.find('#subscriptionepayco_number').val();
    let card_holder = formCard.find('#subscriptionepayco_name').val();
    let card_expire = formCard.find('#subscriptionepayco_expiry').val();
    let card_cvv = formCard.find('#subscriptionepayco_cvc').val();

    card_expire = card_expire.replace(/ /g, '');
    card_expire = card_expire.split('/');
    let month = card_expire[0];
    if (month.length === 1) month = `0${month}`;

    let date = new Date();
    let year = date.getFullYear();
    year = year.toString();
    let lenYear = year.substr(0, 2);

    if(card_expire[1]){
        year = card_expire[1].length === 4 ? card_expire[1]  : lenYear + card_expire[1].substr(-2);
    }

    formCard.find('div.error-subscription-epayco').hide();
    formCard.find('div.error-subscription-epayco span.message').text('');

    if(subscriptionEpaycoValidator()){

        formCard.append($('<input data-epayco="card[number]" type="hidden" />' ).val( number_card ));
        formCard.append($('<input data-epayco="card[name]" type="hidden" />' ).val( card_holder ));
        formCard.append($('<input data-epayco="card[exp_month]" type="hidden" />' ).val( month ));
        formCard.append($('<input data-epayco="card[exp_year]" type="hidden" />' ).val( year ));
        formCard.append($('<input data-epayco="card[cvc]" type="hidden" />' ).val( card_cvv ));

        let errorCard;

        if (!checkCard()){
            errorCard = 'Tipo de tarjeta no aceptada';
        }else if(!valid_credit_card(number_card)){
            errorCard = 'Número de tarjeta, inválida';
        }else if (!validateDate(year, month)){
            errorCard = 'Fecha de caducidad de la tarjeta no válida';
        }

        if(errorCard){
            formCard.find('div.error-subscription-epayco').show();
            formCard.find('div.error-subscription-epayco span.message').text(errorCard);
        }else{
            formCard.find('input[name=form_errors_subscription_epayco]').remove();
            swal.fire({
                title: 'Verificando tarjeta',
                didOpen: () => {
                    swal.showLoading()
                },
                allowOutsideClick: false
            });
            ePayco.token.create(formCard, function(error, token) {
                if(!error) {
                    $.ajax({
                        type: 'POST',
                        url:  subscription_epayco.ajaxurl,
                        data: formCard.serialize() + `&action=subscription_epayco_se_add_new_token&token_card=${token}`,
                        dataType: 'json',
                        beforeSend: () =>{
                            swal.fire({
                                title: 'Agregando tarjeta',
                                didOpen: () => {
                                    swal.showLoading()
                                },
                                allowOutsideClick: false
                            });
                        },
                        success: (r) =>{
                            if (r.status){
                                swal.fire({
                                    title: 'Se ha agregado la tarjeta exitosamente',
                                    type: 'success',
                                    allowOutsideClick: false,
                                    showConfirmButton: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            }else{
                                swal.fire({
                                    title: '¡Lo sentimos!',
                                    text: 'Se ha presentado algún inconveniente al agregar esta tarjeta, intentalo nuevamente por favor.',
                                    type: 'warning'
                                });
                            }
                        }
                    });
                } else {
                    let messageError = error.data.description ?? 'Revise por favor el estado de la tarjeta que esta ingresando';
                    swal.close();
                    formCard.find('div.error-subscription-epayco').show();
                    formCard.find('div.error-subscription-epayco span.message').text(messageError);
                }
            });
        }
    }

}
function subscriptionEpaycoValidator(){

    return !(formCard.find('div.error-subscription-epayco span.message').text() && !formCard.find('input[name=form_errors_subscription_epayco]').length);

}
function loadCard() {
    new Card({
        // a selector or DOM element for the form where users will
        // be entering their information
        form: document.querySelector('#form-epayco'), // *required*
        // a selector or DOM element for the container
        // where you want the card to appear
        container: '.card-wrapper', // *required*

        formSelectors: {
            numberInput: 'input#subscriptionepayco_number', // optional — default input[name="number"]
            expiryInput: 'input#subscriptionepayco_expiry', // optional — default input[name="expiry"]
            cvcInput: 'input#subscriptionepayco_cvc', // optional — default input[name="cvc"]
            nameInput: 'input#subscriptionepayco_name' // optional - defaults input[name="name"]
        },

        width: 200, // optional — default 350px
        formatting: true, // optional - default true

        // Strings for translation - optional
        messages: {
            validDate: 'expire\ndate',
            monthYear: 'mm/yyyy', // optional - default 'month/year'
        },

        // Default placeholders for rendered fields - optional
        placeholders: {
            number: '•••• •••• •••• ••••',
            name: 'Full Name',
            expiry: '••/••',
            cvc: '•••'
        },

        masks: {
            cardNumber: '•' // optional - mask card number
        },

        // if true, will log helpful messages for setting up Card
        debug: false // optional - default false
    });
}


function checkCard(){
    let classCard = $(".jp-card-identified" ).attr( "class" );
    let inputCard = $("input[name=subscriptionepayco_type]");

    let  isAcceptableCard = false;

    switch(true) {
        case (classCard.indexOf('visa') !== -1):
            $(inputCard).val('visa');
            isAcceptableCard = true;
            break;
        case (classCard.indexOf('mastercard') !== -1):
            $(inputCard).val('mastercard');
            isAcceptableCard = true;
            break;
        case (classCard.indexOf('amex') !== -1):
            $(inputCard).val('american-express');
            isAcceptableCard = true;
            break;
        case (classCard.indexOf('diners') !== -1):
            $(inputCard).val('diners');
            isAcceptableCard = true;
    }

    return isAcceptableCard;

}

function valid_credit_card(value) {
    // accept only digits, dashes or spaces
    if (/[^0-9-\s]+/.test(value)) return false;

    // The Luhn Algorithm. It's so pretty.
    var nCheck = 0, nDigit = 0, bEven = false;
    value = value.replace(/\D/g, "");

    for (var n = value.length - 1; n >= 0; n--) {
        var cDigit = value.charAt(n);
        nDigit = parseInt(cDigit, 10);

        if (bEven) {
            if ((nDigit *= 2) > 9) nDigit -= 9;
        }

        nCheck += nDigit;
        bEven = !bEven;
    }

    return (nCheck % 10) === 0;
}

function validateDate(yearEnd, month){

    let date = new Date();
    let currentMonth = ("0" + (date.getMonth() + 1)).slice(-2);
    let year = date.getFullYear();

    return (parseInt(yearEnd) > year) || (parseInt(yearEnd) === year && month >= currentMonth);
}

$(formCard).on( 'submit', subscriptionEpaycoFormHandler );