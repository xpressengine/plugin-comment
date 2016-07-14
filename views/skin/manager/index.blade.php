<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">{{ xe_trans('xe::comment') }} {{ xe_trans('xe::management') }}</h3>
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="pull-left">
                        <form id="__xe_search_form">
                            <div id="__xe_btn_options" class="btn-group btn-fillter" role="group">
                                <input type="hidden" name="options" value="{{ Input::old('options') }}">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                    <li><strong>필터</strong></li>
                                    <li class="active"><a href="#" value="">{{ xe_trans('comment::manage.all') }}</a></li>
                                    <li><a href="#" value="display|{{ Xpressengine\Plugins\Comment\Models\Comment::DISPLAY_VISIBLE }}">{{ xe_trans('comment::manage.public') }}</a></li>
                                    <li><a href="#" value="display|{{ Xpressengine\Plugins\Comment\Models\Comment::DISPLAY_SECRET }}">{{ xe_trans('comment::manage.secret') }}</a></li>
                                    <li><a href="#" value="approved|{{ Xpressengine\Plugins\Comment\Models\Comment::APPROVED_APPROVED }}">{{ xe_trans('comment::manage.approved.approved') }}</a></li>
                                    <li><a href="#" value="approved|{{ Xpressengine\Plugins\Comment\Models\Comment::APPROVED_WAITING }}">{{ xe_trans('comment::manage.approved.waiting') }}</a></li>
                                </ul>
                            </div>
                        </form>
                    </div>
                    <div class="pull-right">
                        <div class="btn-group __xe_tools" role="group" aria-label="...">
                            <button type="button" class="btn btn-default" data-mode="trash">{{ xe_trans('comment::trash') }}</button>
                            <button type="button" class="btn btn-default" data-mode="reject">{{ xe_trans('comment::manage.approved.reject') }}</button>
                            <button type="button" class="btn btn-default" data-mode="approve">{{ xe_trans('comment::manage.approved.approve') }}</button>
                        </div>
                    </div>

                </div>
                <div class="table-responsive">
                    <form id="__xe_form_list" method="post">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <table class="table">
                            <thead>
                            <tr>
                                <th scope="col"><input type="checkbox" id="__xe_check-all"></th>
                                <th scope="col">{{ xe_trans('comment::content') }}</th>
                                <th scope="col">{{ xe_trans('comment::author') }}</th>
                                <th scope="col"><i class="xi-thumbs-up"></i> / <i class="xi-thumbs-down"></i></th>
                                <th scope="col">{{ xe_trans('comment::date.create') }}</th>
                                <th scope="col">{{ xe_trans('comment::ip') }}</th>
                                <th scope="col">{{ xe_trans('comment::display') }}</th>
                                <th scope="col">{{ xe_trans('comment::approve') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($comments as $comment)
                            <tr>
                                <td><input type="checkbox" name="id[]" class="__xe_checkbox" value="{{ $comment->id }}"></td>
                                <td>

                                    <strong>[{{ xe_trans($menuItem($comment)->title) }}]</strong>
                                    {{ str_limit($comment->pureContent, 100) }}
                                    @if($url = $urlMake($comment, $menuItem($comment)))
                                    <a href="{{ $url }}" target="_blank">
                                        <i class="xi-external-link"></i>
                                    </a>
                                    @endif
                                </td>
                                <td><a href="#">{{ $comment->writer }}</a></td>
                                <td>{{ $comment->assentCount }} / {{ $comment->dissentCount }}</td>
                                <td><a href="#">{{ str_replace('-', '.', substr($comment->createdAt, 0, 16)) }}</a></td>
                                <td><a href="#">{{ $comment->ipaddress }}</a></td>
                                <td><span class="label label-green">{{ $comment->display }}</span></td>
                                <td><span class="label label-grey">{{ $comment->approved }}</span></td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </form>
                </div>
                <div class="panel-footer">
                    <div class="pull-left">
                        <nav>
                            {!! $comments->render() !!}
                        </nav>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    $(function () {
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
            eval('actions.' + mode + '($f)');
        });


        $('#__xe_btn_options li > a').click(function (e, flag) {
            e.preventDefault();

            $('#__xe_btn_options input').val($(this).attr('value'));

            $('#__xe_btn_options li').removeClass('active');
            $(this).closest('li').addClass('active');

            if (flag !== true) {
                $(this).closest('form').submit();
            }
        }).each(function () {
            if ($(this).attr('value') == $('#__xe_btn_options input').val()) {
                $(this).triggerHandler('click', [true]);
            }
        });

        var actions = {
            approve: function ($f) {
                $('<input>').attr('type', 'hidden').attr('name', 'approved').val({{ Xpressengine\Plugins\Comment\Models\Comment::APPROVED_APPROVED }}).appendTo($f);

                $f.attr('action', '{{ route('manage.comment.approve') }}');
                $f.submit();
            },
            reject: function ($f) {
                $('<input>').attr('type', 'hidden').attr('name', 'approved').val({{ Xpressengine\Plugins\Comment\Models\Comment::APPROVED_REJECTED }}).appendTo($f);

                $f.attr('action', '{{ route('manage.comment.approve') }}');
                $f.submit();
            },
            trash: function ($f) {
                $f.attr('action', '{{ route('manage.comment.totrash') }}');
                $f.submit();
            }
        };
    });
</script>
