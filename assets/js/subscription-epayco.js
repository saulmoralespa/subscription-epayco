jQuery( function( $ ) {
    'use strict';

    const checkout_form = $( 'form.woocommerce-checkout' );
    const form_subscription_epayco = '#form-epayco';

    $( 'body' ).on( 'updated_checkout', function() {
        $('input[name="payment_method"]').change(function(){
            loadCard();
        }).change();
    });

    $("#wizard").on('onStepChanged', function (event, currentIndex, priorIndex) {
        $('input[name="payment_method"]').change(function(){
            loadCard();
        }).change();
    });

    $(document.body).on('checkout_error', function () {
        swal.close();
    });

    checkout_form.on( 'checkout_place_order', function() {

        if($('form[name="checkout"] input[name="payment_method"]:checked').val() === 'subscription_epayco'){

            let number_card = checkout_form.find('#subscriptionepayco_number').val();
            let card_holder = checkout_form.find('#subscriptionepayco_name').val();
            let card_expire = checkout_form.find('#subscriptionepayco_expiry').val();
            let card_cvv = checkout_form.find('#subscriptionepayco_cvc').val();


            card_expire = card_expire.replace(/ /g, '');
            card_expire = card_expire.split('/');
            let month = card_expire[0];
            if (month.length === 1) month = `0${month}`;

            let date = new Date();
            let year = date.getFullYear();
            year = year.toString();
            let lenYear = year.substr(0, 2);

            let yearEnd = card_expire[1].length === 4 ? card_expire[1]  : lenYear + card_expire[1].substr(-2);

            card_expire = `${month}/${yearEnd}`;

            checkout_form.append($('<input name="subscriptionepayco_number" type="hidden" />' ).val( number_card ));
            checkout_form.append($('<input name="subscriptionepayco_name" type="hidden" />' ).val( card_holder ));
            checkout_form.append($('<input name="subscriptionepayco_expiry" type="hidden" />' ).val( card_expire ));
            checkout_form.append($('<input name="subscriptionepayco_cvc" type="hidden" />' ).val( card_cvv ));

            let inputError = checkout_form.find("input[name=subscriptionepayco_errorcard]");

            if( inputError.length )
            {
                inputError.remove();
            }


            if (!number_card || !card_holder || !card_expire || !card_cvv){
                checkout_form.append(`<input type="hidden" name="subscriptionepayco_errorcard" value="${subscription_epayco.msgEmptyInputs}">`);
            }else if (!checkCard()){
                checkout_form.append(`<input type="hidden" name="subscriptionepayco_errorcard" value="${subscription_epayco.msgNoCard}">`);
            }else if(!valid_credit_card(number_card)){
                checkout_form.append(`<input type="hidden" name="subscriptionepayco_errorcard" value="${subscription_epayco.msgNoCardValidate}">`);
            }else if (!validateDate(yearEnd, month)){
                checkout_form.append(`<input type="hidden" name="subscriptionepayco_errorcard" value="${subscription_epayco.msgValidateDate}">`);
            }

            swal.fire({
                title: subscription_epayco.msjProcess,
                onOpen: () => {
                    swal.showLoading()
                },
                allowOutsideClick: false
            });
        }

    });

    function loadCard() {
        if (checkout_form.find(form_subscription_epayco).is(":visible")){
            new Card({
                form: document.querySelector(form_subscription_epayco),
                container: '.card-wrapper'
            });
        }
    }

    function checkCard(){
        let countryCode = subscription_epayco.country;
        let classCard = $(".jp-card-identified" ).attr( "class" );
        let inputCard = $("input[name=subscriptionepayco_type]");

        let  isAcceptableCard = false;

        switch(true) {
            case (classCard.indexOf('visa') !== -1 && countryCode !== 'PA'):
                $(inputCard).val('VISA');
                isAcceptableCard = true;
                break;
            case (classCard.indexOf('mastercard') !== -1):
                $(inputCard).val('MASTERCARD');
                isAcceptableCard = true;
                break;
            case (classCard.indexOf('amex') !== -1 && countryCode !== 'PA'):
                $(inputCard).val('AMEX');
                isAcceptableCard = true;
                break;
            case (classCard.indexOf('diners') !== -1 && (countryCode !== 'MX' || countryCode !== 'PA') ):
                $(inputCard).val('DINERS');
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

});