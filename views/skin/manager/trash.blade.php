<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">{{ xe_trans('xe::trash') }}</h3>
                    </div>
                </div>

                <div class="panel-heading">
                    <div class="pull-right">
                        <div class="btn-group" role="group" aria-label="...">
                            <button type="button" class="btn btn-default __xe_btn_restore">{{ xe_trans('comment::restore') }}</button>
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
                                <th scope="col">{{ xe_trans('comment::date.delete') }}</th>
                                <th scope="col">{{ xe_trans('comment::ip') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($comments as $comment)
                                <tr>
                                    <td><input type="checkbox" name="id[]" class="__xe_checkbox" value="{{ $comment->id }}"></td>
                                    <td>
                                        <strong>[{{ xe_trans($menuItem($comment)->title) }}]</strong>
                                        {{ str_limit(strip_tags($comment->content), 100) }}
                                    </td>
                                    <td><a href="#">{{ $comment->writer }}</a></td>
                                    <td>{{ $comment->assentCount }} / {{ $comment->dissentCount }}</td>
                                    <td><a href="#">{{ str_replace('-', '.', substr($comment->createdAt, 0, 16)) }}</a></td>
                                    <td><a href="#">{{ str_replace('-', '.', substr($comment->deletedAt, 0, 16)) }}</a></td>
                                    <td><a href="#">{{ $comment->ipaddress }}</a></td>
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
    window.onload = function () {
        $('#__xe_check-all').change(function () {
            if ($(this).is(':checked')) {
                $('input.__xe_checkbox').prop('checked', true);
            } else {
                $('input.__xe_checkbox').prop('checked', false);
            }
        });

        $('.__xe_btn_restore').click(function () {
            var flag = false;

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

            $f.attr('action', '{{ route('manage.comment.restore') }}');
            $f.submit();
        });
    };
</script>