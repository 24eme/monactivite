$(document).ready( function() {
    moment.locale('fr');
    $('input.minicolors').minicolors({
        theme: 'bootstrap'
    });

    var initAdvancedElements = function() {
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="tooltipHtml"]').tooltip({'html': true, 'container': 'body', 'template': '<div style="min-width: 250px; max-width: 400px; max-height: 170px;" class="tooltip tooltipHtml" role="tooltip"><div class="tooltip-arrow"></div><div style="min-width: 250px; max-width: 400px; display: block; text-align: left; overflow: hidden; max-height: 170px;" class="tooltip-inner"></div></div>'});
    }

    initAdvancedElements();

    $('.iconpicker[data-form-item-relation]').on('change', function(e) {
        var icon = e.icon.replace("glyphicon-", "");
        if(icon == "empty") {

            icon = null;
        }
        $($(this).attr('data-form-item-relation')).val(icon);
    });

    $('.submit-on-change').on('change', function() {
        $(this).parents('form').submit();
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
            var newContent = $(data);
            newContent.hide();
            newContent.insertAfter($("#activities_container .list-end:last"));
            initAdvancedElements();
            newContent.fadeIn(1000);
        });
    });


    $(document).on('scroll', function(e) {
        if(!$('#btn_load_more_activities').attr('disabled') && ($(document).scrollTop() + ($(window).height() * 0.35)) >  ($(document).height() - $(window).height())) {
            $('#btn_load_more_activities').click();
        }
    })

    $.fn.select2.defaults.set( "theme", "bootstrap" );

    $('.select2').select2({
        theme: "bootstrap",
        tags: true,
        templateResult: function(state) {
            if(state.new) {
                return $("<span><span style=\"opacity: 0.6;\">Ajouter : </span>"+state.text + "</span>");
            }

            return state.text;
        },
        createTag: function (params) {
            var term = $.trim(params.term);

            if (term === '') {
              return null;
            }

            return {
              id: params.term,
              text: params.term,
              new: true
            }
        },
        insertTag: function (data, tag) {
           data.push(tag);
        }
    });

    $('.dropdown-toggle').dropdown();

    $('#btn-select-time').daterangepicker({
        startDate: moment($('#input-date-to').val()),
        endDate:  moment($('#input-date-from').val()),
        ranges: {
           'le mois en cours': [moment().startOf('month'), moment()],
           'le dernier mois': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
           '6 mois': [moment().subtract(6, 'month'), moment()],
           '1 an': [moment().subtract(1, 'year'), moment()],
           '2 ans': [moment().subtract(2, 'year'), moment()],
           '5 ans': [moment().subtract(5, 'year'), moment()]
        }
    }, function(start, end) {
        $('#input-date-to').val(start.format('YYYY-MM-DD'));
        $('#input-date-from').val(end.format('YYYY-MM-DD'));
        $('#form-search').submit();
    });

    var lastTag = null;

    $('body').on('click', function() {
        lastTag = null;
    });

    $('#activities_container').on('click', 'button.btn-tag-empty', function(e) {
        $('#modal-tag-add #activity_tag_add_activity_id').val($(this).parents('.ligne').data('id'));
        if(lastTag) {
            lastTag.click();

            return e.preventDefault();
        }
        $('#modal-tag-add #activity_tag_add_tag_id').val("");
        $('#modal-tag-add').data('fromview', null);
        $('#modal-tag-add').modal();
    });

    $('#modal-default').on('click', 'button.btn-tag-empty', function() {
        $('#modal-default').modal('hide');
        $('#modal-tag-add #activity_tag_add_activity_id').val($('#modal_activity_view').data('id'));
        $('#modal-tag-add #activity_tag_add_tag_id').val("");
        $('#modal-tag-add').data('fromview', 1);
        $('#modal-tag-add').modal();
    });

    var suppressionTag = function(button, ligne) {
        if(!confirm("Voulez vous supprimer ce tag ?")) {

            return;
        }

        lastTag = null;

        $('#activity_tag_delete_activity_id').val(ligne.data('id'));
        $('#activity_tag_delete_tag_id').val(button.data('id'));
        var buttonTarget = ligne.find('.itemTags .btn').first();

        var buttonEmpty = $('<button type="button" class="btn btn-sm btn-default btn-tag-empty"><span class="glyphicon glyphicon-plus"></span></button>');

        var form = $('#form_tag_remove_container form');
        $.post(form.attr('action'), form.serialize(), function() {
            if(ligne.find('.itemTags .btn[data-id='+button.data('id')+']').remove().length > 0) {
                buttonEmpty.insertBefore(buttonTarget);
            }
            button.remove();
        });
    };

    $('#modal-default').on('click', 'button.btn-tag', function() {

        return suppressionTag($(this), $('.ligne[data-id='+$('#modal_activity_view').data('id')+']'));
    });

    $('#activities_container').on('click', 'button.btn-tag-small', function() {

        return suppressionTag($(this), $(this).parents('.ligne'));
    });

    $('#activities_container').on('click', '.btn-activity-view', function() {
        $('#modal-default').load($(this).attr('href'));
        $('#modal-default').modal();

        return false;
    });

    $('#modal-tag-add .btn-tag').on('click', function(e) {
        lastTag = null;
        var buttonClicked = $(this);
        $('#modal-tag-add #activity_tag_add_tag_id').val($(this).parents('.tag').data('id'));
        var ligne = $('.ligne[data-id='+$('#modal-tag-add #activity_tag_add_activity_id').val()+']');
        var buttonTarget = ligne.find('.btn-tag-empty').last();
        var button = $(this).parents('.tag').find('.btn-tag-small').clone();
        var form = $('#modal-tag-add form');
        $.post(form.attr('action'), form.serialize(), function() {
            button.insertBefore(buttonTarget);
            buttonTarget.remove();
            lastTag = buttonClicked;
        });

        $('#modal-tag-add').modal("hide");

        if($('#modal-tag-add').data('fromview')) {
            ligne.find('.btn-activity-view').click();
        }

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

    $('#btn_load_more_activities').click();
});
