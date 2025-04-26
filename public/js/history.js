jQuery(document).ready(function($) {
    // Gestion du menu toggle pour mobile
    $('.menu-toggle').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log("Clic sur menu-toggle détecté");
        $('.fh5co-nav .menu-1 ul').toggleClass('active');
    });

    // Gestion des dropdowns sur mobile
    $('.fh5co-nav .menu-1 ul li.has-dropdown > a').click(function(e) {
        if ($(window).width() <= 768) {
            console.log("Clic sur dropdown détecté");
            e.preventDefault();
            e.stopPropagation();
            $(this).parent().toggleClass('active');
        }
    });

    // Gestion du survol pour les dropdowns sur desktop
    $('.fh5co-nav .menu-1 ul li.has-dropdown').hover(
        function() {
            if ($(window).width() > 768) {
                console.log("Survol détecté sur un élément has-dropdown");
                $(this).find('.dropdown').css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            }
        },
        function() {
            if ($(window).width() > 768) {
                console.log("Fin du survol");
                $(this).find('.dropdown').css({
                    'display': 'none',
                    'visibility': 'hidden',
                    'opacity': '0'
                });
            }
        }
    );

    // Gestion de la popup QR code
    $('.show-qr-code').click(function(e) {
        e.preventDefault();
        var qrUrl = $(this).data('qr-url');
        var reclamationId = $(this).data('reclamation-id');
        console.log('Ouverture de la popup QR code', { qrUrl: qrUrl, reclamationId: reclamationId });
        $('#qrCodePopupTitle').text('QR Code - Réclamation #' + reclamationId);
        $('#qrCodePopupImage').attr('src', qrUrl).show();
        $('.qr-code-popup .error').hide();
        $('#qrCodePopup').fadeIn();
    });

    $('#qrCodePopupImage').on('error', function() {
        console.log('Erreur de chargement du QR code');
        $(this).hide();
        $('.qr-code-popup .error').show();
    });

    $('#closeQrCodePopup').click(function(e) {
        e.preventDefault();
        console.log('Fermeture de la popup QR code');
        $('#qrCodePopup').fadeOut();
    });

    // Ferme la popup si on clique à l'extérieur
    $(document).click(function(e) {
        if (!$(e.target).closest('#qrCodePopup, .show-qr-code').length) {
            console.log('Clic à l\'extérieur, fermeture de la popup');
            $('#qrCodePopup').fadeOut();
        }
    });
});