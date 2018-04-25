<?php
use Xpressengine\Plugins\Comment\Models\Comment;
?>
<div class="row">
    <div class="col-sm-12">
        <div class="panel-group">
            <div class="panel">
                <div class="panel-heading">
                    <div class="pull-left">
                        <h3 class="panel-title">{{ xe_trans('xe::comment') }} {{ xe_trans('xe::management') }} </h3>
                            ( {{xe_trans('comment::searchCommentCount') }} : {{$comments->total()}} / {{xe_trans('comment::totalCommentCount')}}  : {{$totalCount}} )
                    </div>
                </div>
                <div class="panel-heading">
                    <div class="pull-left">
                        <div class="btn-group __xe_tools" role="group" aria-label="...">
                            <button type="button" class="btn btn-default" data-mode="trash">{{ xe_trans('comment::trash') }}</button>
                            <button type="button" class="btn btn-default" data-mode="approve">{{ xe_trans('comment::manage.approved.approve') }}</button>
                            <button type="button" class="btn btn-default" data-mode="reject">{{ xe_trans('comment::manage.approved.reject') }}</button>
                        </div>
                    </div>
                    <div class="pull-right">

                            <form id="__xe_search_form" class="input-group search-group">
                                <div id="__xe_btn_options" class="input-group-btn btn-fillter" role="group">
                                    <input type="hidden" name="options" value="{{ Request::old('options') }}">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        <span class="__xe_text"> {{ $statusMessage }} </span> <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li class="active"><a href="#" value="">{{ xe_trans('comment::manage.all') }}</a></li>
                                        <li><a href="#" value="display|{{ Comment::DISPLAY_VISIBLE }}">{{ xe_trans('comment::manage.public') }}</a></li>
                                        <li><a href="#" value="display|{{ Comment::DISPLAY_SECRET }}">{{ xe_trans('comment::manage.secret') }}</a></li>
                                        <li><a href="#" value="approved|{{ Comment::APPROVED_APPROVED }}">{{ xe_trans('comment::manage.approved.approved') }}</a></li>
                                        <li><a href="#" value="approved|{{ Comment::APPROVED_WAITING }}">{{ xe_trans('comment::manage.approved.waiting') }}</a></li>
                                        <li><a href="#" value="approved|{{ Comment::APPROVED_REJECTED }}">{{ xe_trans('comment::manage.approved.reject') }}</a></li>
                                    </ul>
                                </div>

                                <div class="input-group-btn __xe_btn_search_target">
                                    <input type="hidden" name="search_target" value="{{ Request::get('search_target') }}">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="__xe_text">{{Request::has('search_target') && Request::get('search_target') != '' ? xe_trans('comment::' . $searchTargetWord) : xe_trans('xe::select')}}</span> <span class="caret"></span></button>
                                    <ul class="dropdown-menu" role="menu">
                                        <li @if(Request::get('search_target') == '') class="active" @endif><a href="#" value="">{{xe_trans('comment::select')}}</a></li>
                                        <li @if(Request::get('search_target') == 'content') class="active" @endif><a href="#" value="content">{{xe_trans('comment::content')}}</a></li>
                                        <li @if(Request::get('search_target') == 'author') class="active" @endif><a href="#" value="author">{{xe_trans('comment::author')}}</a></li>
                                        <li @if(Request::get('search_target') == 'ip') class="active" @endif><a href="#" value="ip">{{xe_trans('comment::ip')}}</a></li>
                                    </ul>
                                </div>
                                <div class="search-input-group">
                                    <input type="text" name="search_keyword" class="form-control" aria-label="Text input with dropdown button" placeholder="{{xe_trans('xe::enterKeyword')}}" value="{{Request::get('search_keyword')}}">
                                    <button class="btn-link">
                                        <i class="xi-search"></i><span class="sr-only">{{xe_trans('xe::search')}}</span>
                                    </button>
                                </div>

                            </form>

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
                                    <strong>[{{ xe_trans($menuItem($comment)->title) }} -
                                        @if ($comment->getTarget()->title != null)
                                            {{ $comment->getTarget()->title }}]
                                        @else
                                            {{ xe_trans('comment::deletedBoardName') }}]
                                        @endif
                                    </strong>

                                    {{ str_limit($comment->pure_content, 100) }}
                                    @if($url = $urlMake($comment, $menuItem($comment)))
                                    <a href="{{ $url }}" target="_blank">
                                        <i class="xi-external-link"></i>
                                    </a>
                                    @endif
                                </td>
                                <td><a href="#">{{ $comment->writer }}</a></td>
                                <td>{{ $comment->assent_count }} / {{ $comment->dissent_count }}</td>
                                <td><a href="#">{{ str_replace('-', '.', substr($comment->created_at, 0, 16)) }}</a></td>
                                <td><a href="#">{{ $comment->ipaddress }}</a></td>
                                <td><span class="label label-green">{{ xe_trans($comment->getDisplayStatusName($comment->display)) }}</span></td>
                                <td><span class="label label-grey">{{ xe_trans($comment->getApproveStatusName($comment->approved)) }}</span></td>
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

<script>
// @FIXME 파일 분리
window.jQuery(function ($) {
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

    $('.__xe_btn_search_target .dropdown-menu a').click(function (e) {
        e.preventDefault();

        $('[name="search_target"]').val($(this).attr('value'));
        $('.__xe_btn_search_target .__xe_text').text($(this).text());

        $(this).closest('.dropdown-menu').find('li').removeClass('active');
        $(this).closest('li').addClass('active');
    });

    var actions = {
        approve: function ($f) {
            $('<input>').attr('type', 'hidden').attr('name', 'approved').val({{ Comment::APPROVED_APPROVED }}).appendTo($f);

            $f.attr('action', '{{ route('comment::manage.approve') }}');
            $f.submit();
        },
        reject: function ($f) {
            $('<input>').attr('type', 'hidden').attr('name', 'approved').val({{ Comment::APPROVED_REJECTED }}).appendTo($f);

            $f.attr('action', '{{ route('comment::manage.approve') }}');
            $f.submit();
        },
        trash: function ($f) {
            $f.attr('action', '{{ route('comment::manage.totrash') }}');
            $f.submit();
        }
    };
});
</script>
