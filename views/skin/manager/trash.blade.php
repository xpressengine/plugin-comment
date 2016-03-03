<div class="panel">
    <div class="panel-heading">
        <div class="row">
            <div class="col-sm-12">

                <div class="admin_card">
                    <div class="card_tit">

                        <div class="btn-group pull-right">
                            <button type="button" class="btn_setting __xe_btn_restore">
                                <i class="xi-repeat"></i>
                                {{ xe_trans('comment::restore') }}
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
                                            <th>{{ xe_trans('comment::date.delete') }}</th>
                                            <th>{{ xe_trans('comment::ip') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($comments as $comment)
                                            <tr>
                                                <td><input type="checkbox" name="id[]" class="__xe_checkbox" value="{{ $comment->id }}"></td>
                                                <td>
                                                    {{--{{ str_repeat('&nbsp;&nbsp;', $comment->indent()) }}@if($comment->indent() > 0) ㄴ @endif--}}
                                                    <b>[{{ $comment->instanceId }}]</b> {{ str_limit(strip_tags($comment->content), 100) }}
                                                </td>
                                                <td>{{ $comment->writer }}</td>
                                                <td>{{ $comment->assentCount }} / {{ $comment->dissentCount }}</td>
                                                <td>{{ str_replace('-', '.', substr($comment->createdAt, 0, 16)) }}</td>
                                                <td>{{ str_replace('-', '.', substr($comment->deletedAt, 0, 16)) }}</td>
                                                <td>{{ $comment->ipaddress }}</td>
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