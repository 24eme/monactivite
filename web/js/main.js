$(document).ready( function() {
    $('input.minicolors').minicolors({
        theme: 'bootstrap'
    });

    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();

    $('.iconpicker[data-form-item-relation]').on('change', function(e) {
        var icon = e.icon.replace("glyphicon-", "");
        if(icon == "empty") {

            icon = null;
        }
        $($(this).attr('data-form-item-relation')).val(icon);
    });

    $('#activities_container').on('click', '#btn_load_more_activities', function() {
        var button = $(this);
        button.button('loading');
        $.get($(this).attr('data-url'), function(data, status) {
            if(!status) {
                button.button('reset');
                return;
            }
            $('#btn_load_more_container').remove();
            $('#activities_container').html($('#activities_container').html()+"\n"+data);
        });
    });
});
