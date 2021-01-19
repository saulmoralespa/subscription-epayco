jQuery( function( $ ) {

    const $checkout_form = $( 'form.checkout, form#order_review' );
    const form_subscription_epayco = '#form-epayco';

    $( document.body ).on( 'updated_checkout', function() {
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
        $checkout_form.find('input[name=subscription_epayco_card]').remove();
    });

    function subscriptionEpaycoFormHandler(){
        if($('form[name="checkout"] input[name="payment_method"]:checked').val() === 'subscription_epayco'){

            if (!$( 'input[name=subscription_epayco_card]' ).length){

                if(subscriptionEpaycoValidator()){

                    $checkout_form.find('div.error-subscription-epayco').hide();
                    $checkout_form.find('div.error-subscription-epayco span.message').text('');

                    let number_card = $checkout_form.find('#subscriptionepayco_number').val();
                    let card_holder = $checkout_form.find('#subscriptionepayco_name').val();
                    let card_expire = $checkout_form.find('#subscriptionepayco_expiry').val();
                    let card_cvv = $checkout_form.find('#subscriptionepayco_cvc').val();

                    card_expire = card_expire.replace(/ /g, '');
                    card_expire = card_expire.split('/');
                    let month = card_expire[0];
                    if (month.length === 1) month = `0${month}`;

                    let date = new Date();
                    let year = date.getFullYear();
                    year = year.toString();
                    let lenYear = year.substr(0, 2);
                    let yearEnd = 0;

                    if(card_expire[1]){
                        yearEnd = card_expire[1].length === 4 ? card_expire[1]  : lenYear + card_expire[1].substr(-2);
                        card_expire = `${month}/${yearEnd}`;
                    }

                    $checkout_form.append($('<input name="subscriptionepayco_number" type="hidden" />' ).val( number_card ));
                    $checkout_form.append($('<input name="subscriptionepayco_name" type="hidden" />' ).val( card_holder ));
                    $checkout_form.append($('<input name="subscriptionepayco_expiry" type="hidden" />' ).val( card_expire ));
                    $checkout_form.append($('<input name="subscriptionepayco_cvc" type="hidden" />' ).val( card_cvv ));

                    let errorCard;

                    if (!number_card || !card_holder || !card_expire || !card_cvv || !(card_cvv.length >= 3 && card_cvv.length < 5)){
                        errorCard = subscription_epayco.msgEmptyInputs;
                    }else if (!checkCard()){
                        errorCard = subscription_epayco.msgNoCard;
                    }else if(!valid_credit_card(number_card)){
                        errorCard = subscription_epayco.msgNoCardValidate;
                    }else if (!validateDate(yearEnd, month)){
                        errorCard = subscription_epayco.msgValidateDate;
                    }

                    if(errorCard){
                        $checkout_form.find('div.error-subscription-epayco').show();
                        $checkout_form.find('div.error-subscription-epayco span.message').text(errorCard);
                        $checkout_form.append( '<input type="hidden" class="form_errors" name="form_errors_subscription_epayco" value="1">' );
                    }else{
                        $checkout_form.find('input[name=form_errors_subscription_epayco]').remove();
                        $checkout_form.append($('<input name="subscription_epayco_card" type="hidden" />' ).val( 1 ));
                        $checkout_form.submit();
                    }

                    return false;
                }
            }
        }

        return true;
    }

    function subscriptionEpaycoValidator(){

        return !($checkout_form.find('div.error-subscription-epayco span.message').text() && !$checkout_form.find('input[name=form_errors_subscription_epayco]').length);

    }

    function loadCard() {
        if ($checkout_form.find(form_subscription_epayco).is(":visible")){

            new Card({
                // a selector or DOM element for the form where users will
                // be entering their information
                form: document.querySelector(form_subscription_epayco), // *required*
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

    $( 'form.checkout' ).on( 'checkout_place_order', subscriptionEpaycoFormHandler );

    // Pay Page Form
    $( 'form#order_review' ).on( 'submit', subscriptionEpaycoFormHandler );
});