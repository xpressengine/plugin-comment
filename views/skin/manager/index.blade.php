<div class="panel">
    <div class="panel-heading">
        <div class="row">
            <div class="col-sm-12">

                <div class="admin_card">
                    <div class="card_tit">

                        <div class="form-inline pull-left">
                            <form id="__xe_search_form">
                                <div class="slct_area" id="__xe_btn_options">
                                    <input type="hidden" name="options" value="{{ Input::old('options') }}">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="text"></span></a>
                                    <div class="slct_lst trasition dropdown-menu">
                                        <ul>
                                            <li><a href="#" class="item" value="">{{ xe_trans('comment::manage.all') }}</a></li>
                                            <li><a href="#" class="item" value="display|visible">{{ xe_trans('comment::manage.public') }}</a></li>
                                            <li><a href="#" class="item" value="display|secret">{{ xe_trans('comment::manage.secret') }}</a></li>
                                            <li><a href="#" class="item" value="approved|approved">{{ xe_trans('comment::manage.approved.approved') }}</a></li>
                                            <li><a href="#" class="item" value="approved|waiting">{{ xe_trans('comment::manage.approved.waiting') }}</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="pull-right __xe_tools">
                            <button type="button" class="btn_setting" data-mode="trash">
                                <i class="xi-trash"></i>
                                {{ xe_trans('comment::trash') }}
                            </button>
                            <button type="button" class="btn_setting" data-mode="reject">
                                <i class="xi-close-circle"></i>
                                {{ xe_trans('comment::manage.approved.reject') }}
                            </button>
                            <button type="button" class="btn_setting" data-mode="approve">
                                <i class="xi-check-circle"></i>
                                {{ xe_trans('comment::manage.approved.approve') }}
                            </button>
                        </div>

                    </div>
                    <div class="card_cont">

                        <div class="box box-primary mg-bottom">
                            <form id="__xe_form_list" method="post">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <div class="box-body no-padding">
                                    <table class="table table-hover">
                                        <thead>
                                        <tr>
                                            <th><input type="checkbox" id="__xe_check-all"></th>
                                            <th>{{ xe_trans('comment::content') }}</th>
                                            <th>{{ xe_trans('comment::author') }}</th>
                                            <th><i class="xi-thumbs-up"></i> / <i class="xi-thumbs-down"></i></th>
                                            <th>{{ xe_trans('comment::date.create') }}</th>
                                            <th>{{ xe_trans('comment::ip') }}</th>
                                            <th>{{ xe_trans('comment::display') }}</th>
                                            <th>{{ xe_trans('comment::approve') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($comments as $comment)
                                            <tr>
                                                <td><input type="checkbox" name="id[]" class="__xe_checkbox" value="{{ $comment->id }}"></td>
                                                <td>
                                                    <b>[{{ xe_trans($menuItem($comment)->title) }}]</b>
                                                    <a href="{{ $urlMake($comment, $menuItem($comment)) }}" target="_blank">
                                                    {{ str_limit(strip_tags($comment->content), 100) }}
                                                    </a>
                                                </td>
                                                <td>{{ $comment->writer }}</td>
                                                <td>{{ $comment->assentCount }} / {{ $comment->dissentCount }}</td>
                                                <td>{{ str_replace('-', '.', substr($comment->createdAt, 0, 16)) }}</td>
                                                <td>{{ $comment->ipaddress }}</td>
                                                <td>{{ $comment->display }}</td>
                                                <td>{{ $comment->approved }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <nav class="text-center">{!! $comments->render() !!}</nav>

    </div>
</div>

<script type="text/javascript">
    window.onload = function () {
        $('#__xe_check-all').change(function () {
            if ($(this).is(':checked')) {
                $('input.__xe_checkbox').prop('checked', true);
            } else {
                $('input.__xe_checkbox').prop('checked', false);
            }
        });

        $('.__xe_tools button').click(function () {
            var mode = $(this).attr('data-mode'), flag = false;

            $('input.__xe_checkbox').each(function () {
                if ($(this).is(':checked')) {
                    flag = true;
                }
            });

            if (flag !== true) {
                return;
            }

            var $f = $('#__xe_form_list');
            $('<input>').attr('type', 'hidden').attr('name', 'redirect').val(location.href).appendTo($f);

            eval('actions.' + mode + '($f)');
        });

//        $('#__xe_btn_options > a').click(function (e) {
//            e.preventDefault();
//            $(this).parent().toggleClass('open');
//        });

        $('#__xe_btn_options .item').click(function (e, flag) {
            e.preventDefault();

            $('#__xe_btn_options input').val($(this).attr('value'));
            $('#__xe_btn_options .text').text($(this).text());

            if (flag !== true) {
                $(this).closest('form').submit();
            }
        }).each(function () {
            if ($(this).attr('value') == "{{ Input::old('options') }}") {
                $(this).triggerHandler('click', [true]);
            }
        });
    };

    var actions = {
        approve: function ($f) {
            $('<input>').attr('type', 'hidden').attr('name', 'approved').val('approved').appendTo($f);

            $f.attr('action', '{{ route('manage.comment.approve') }}');
            $f.submit();
        },
        reject: function ($f) {
            $('<input>').attr('type', 'hidden').attr('name', 'approved').val('rejected').appendTo($f);

            $f.attr('action', '{{ route('manage.comment.approve') }}');
            $f.submit();
        },
        trash: function ($f) {
            $f.attr('action', '{{ route('manage.comment.totrash') }}');
            $f.submit();
        }
    };
</script>