$(document).ready( function() {
    $('input.minicolors').minicolors({
        theme: 'bootstrap'
    });

    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="tooltipHtml"]').tooltip({'html': true, 'container': 'body', 'template': '<div style="min-width: 250px; max-width: 400px; max-height: 170px;" class="tooltip tooltipHtml" role="tooltip"><div class="tooltip-arrow"></div><div style="min-width: 250px; max-width: 400px; display: block; text-align: left; overflow: hidden; max-height: 170px;" class="tooltip-inner"></div></div>'});

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

    $('.select2').select2({
        theme: 'bootstrap'
    });

    $('.dropdown-toggle').dropdown();

    $('#activities_container').on('click', '.btn-activity-tag', function() {
        $('#modal-tag-add #activity_tag_add_activity_id').val($(this).parents('.ligne').data('id'));
        $('#modal-tag-add #activity_tag_add_tag_id').val("-1");
        $('#modal-tag-add').modal();
    });

    $('#modal-tag-add').on('show.bs.modal', function (event) {
        console.log(event);
    })

    $('#modal-tag-add .btn-tag').on('click', function(e) {
        $('#modal-tag-add #activity_tag_add_tag_id').val($(this).parents('.tag').data('id'));
        var buttonTarget = $('.ligne[data-id='+$('#modal-tag-add #activity_tag_add_activity_id').val()+'] .btn-tag-empty').last();
        var button = $(this).parents('.tag').find('.btn-tag-small');
        button.insertBefore(buttonTarget);
        buttonTarget.remove();
        var form = $('#modal-tag-add form');
        $.post(form.attr('action'), form.serialize(), function() {});
        $('#modal-tag-add').modal("hide");
        e.preventDefault();
    });

    $('#btn_execute_update').on('click', function(e) {
        e.preventDefault();

        var btn = $(this).button('loading');
        $('#update_result #update_result_info').html("");
        $('#update_result').addClass('hidden');
        btn.addClass('btn-info');

        $.get($(this).attr('href'), function(data) {
            btn.removeClass('btn-info');
            btn.removeClass('btn-primary');
            btn.addClass('btn-default');
            $('#update_result #update_result_info').html(data.replace(/\n/g, '<br />'));
            $('#update_result').removeClass('hidden');
            btn.button('reset');
        });

        return false;
    });
});
