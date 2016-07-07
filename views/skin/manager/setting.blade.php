@section('page_title')
    <h2>{{xe_trans('comment::manage.detailSetting')}}</h2>
@endsection

@section('page_description')
    <small>{{ xe_trans('comment::manage.detailSettingDesc') }}</small>
@endsection

@section('content_bread_crumbs')
    <a href="{{ XeMenu::getInstanceSettingURI($menuItem) }}"><i class="xi-arrow-left"></i> {{xe_trans($menuItem->title)}}</a>
@endsection


<div class="panel-group" id="accordion-comment" role="tablist" aria-multiselectable="true">
    <!-- Comment dynamic field box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{xe_trans('comment::manage.detailSetting')}}</h3>
            </div>
            <div class="pull-right">
                <a data-toggle="collapse" data-parent="#accordion" href="#commentBasic" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">메뉴닫기</span></a>
            </div>
        </div>
        <div id="commentBasic" class="panel-collapse collapse in">
            <form id="fCommentSetting" method="post" action="{{ route('manage.comment.setting', $targetInstanceId) }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="panel-body">

                    <div class="panel">
                        <div class="panel-heading">
                            <div class="pull-left">
                                <h4 class="panel-title">{{ xe_trans('comment::manage.setting.basic') }}</h4>
                            </div>
                        </div>
                        <div class="panel-body">
                            {{--<div class="row">--}}
                                {{--<div class="col-sm-6">--}}
                                    {{--<div class="form-group">--}}
                                        {{--<div class="clearfix">--}}
                                            {{--<label>{{ xe_trans('comment::manage.division') }}</label>--}}
                                        {{--</div>--}}
                                        {{--<input type="text" class="form-control" value="{{ $config->get('division') ? 'Used' : 'Unused' }}" readonly="readonly" disabled="disabled">--}}
                                    {{--</div>--}}
                                {{--</div>--}}
                            {{--</div>--}}

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>{{ xe_trans('comment::manage.perPage') }}</label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ $config->getPure('perPage') === null ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <input type="text" class="form-control" name="perPage" value="{{ $config->get('perPage') }}">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>{{ xe_trans('comment::manage.ordering') }}</label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ $config->getPure('reverse') === null ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <select name="reverse" class="form-control">
                                            <option value="false" @if(!$config->get('reverse')) selected @endif>{{ xe_trans('comment::forwardOrder') }}</option>
                                            <option value="true" @if($config->get('reverse')) selected @endif>{{ xe_trans('comment::inverseOrder') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-6">

                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>{{ xe_trans('comment::manage.useApprove') }}</label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ $config->getPure('useApprove') === null ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <select name="useApprove" class="form-control">
                                            <option value="true" @if($config->get('useApprove')) selected @endif>{{ xe_trans('xe::use') }}</option>
                                            <option value="false" @if(!$config->get('useApprove')) selected @endif>{{ xe_trans('xe::disuse') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>{{ xe_trans('comment::manage.secret') }}</label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ $config->getPure('secret') === null ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <select name="secret" class="form-control">
                                            <option value="true" @if($config->get('secret')) selected @endif>{{ xe_trans('xe::use') }}</option>
                                            <option value="false" @if(!$config->get('secret')) selected @endif>{{ xe_trans('xe::disuse') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>{{ xe_trans('comment::manage.assent') }}</label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ $config->getPure('useAssent') === null ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <select name="useAssent" class="form-control">
                                            <option value="true" @if($config->get('useAssent')) selected @endif>{{ xe_trans('xe::use') }}</option>
                                            <option value="false" @if(!$config->get('useAssent')) selected @endif>{{ xe_trans('xe::disuse') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>{{ xe_trans('comment::manage.dissent') }}</label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ $config->getPure('useDissent') === null ? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <select name="useDissent" class="form-control">
                                            <option value="true" @if($config->get('useDissent')) selected @endif>{{ xe_trans('xe::use') }}</option>
                                            <option value="false" @if(!$config->get('useDissent')) selected @endif>{{ xe_trans('xe::disuse') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>
                                                {{ xe_trans('comment::manage.removeType') }}
                                                <small>{{ xe_trans('comment::manage.explainRemoveType') }}</small>
                                            </label>
                                            <div class="checkbox pull-right">
                                                <label>
                                                    <input type="checkbox" class="__xe_inherit" {{ !$config->getPure('removeType')? 'checked' : '' }}>
                                                    {{ xe_trans('xe::inheritMode') }}
                                                </label>
                                            </div>
                                        </div>
                                        <select name="removeType" class="form-control">
                                            <option value="{{ Xpressengine\Plugins\Comment\Handler::REMOVE_BATCH }}" @if($config->get('removeType') == Xpressengine\Plugins\Comment\Handler::REMOVE_BATCH) selected @endif>{{ xe_trans('comment::removeBatch') }}</option>
                                            <option value="{{ Xpressengine\Plugins\Comment\Handler::REMOVE_BlIND }}" @if($config->get('removeType') == Xpressengine\Plugins\Comment\Handler::REMOVE_BlIND) selected @endif>{{ xe_trans('comment::removeBlind') }}</option>
                                            <option value="{{ Xpressengine\Plugins\Comment\Handler::REMOVE_UNABLE }}" @if($config->get('removeType') == Xpressengine\Plugins\Comment\Handler::REMOVE_UNABLE) selected @endif>{{ xe_trans('comment::removeUnable') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.permission.create') }}</label>
                                <div class="well">
                                    {!! uio('permission', $permArgs['create']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary"><i class="xi-download"></i>{{ xe_trans('xe::save') }}</button>
                    </div>
                </div>
            </form>

        </div>
    </div>


    <!-- Skin config box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{ xe_trans('xe::skin') }}</h3>
            </div>
            <div class="pull-right">
                <a data-toggle="collapse" data-parent="#accordion" href="#commentSkinSection" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">메뉴닫기</span></a>
            </div>
        </div>
        <div id="commentSkinSection" class="panel-collapse collapse in">
            <div class="panel-body">
                {!! $skinSection !!}
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{xe_trans('xe::editor')}}</h3>
            </div>
            <div class="pull-right">
                <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">메뉴닫기</span></a>
            </div>
        </div>
        <div id="collapseTwo" class="panel-collapse collapse in">
            <div class="panel-body">
                {!! $editorSection !!}
            </div>
        </div>
    </div>

    <!-- Comment dynamic field box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{ xe_trans('xe::dynamicField') }}</h3>
            </div>
            <div class="pull-right">
                <a data-toggle="collapse" data-parent="#accordion" href="#commentDynamicField" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">메뉴닫기</span></a>
            </div>
        </div>
        <div id="commentDynamicField" class="panel-collapse collapse in">
            <div class="panel-body">
                {!! $dynamicFieldSection !!}
            </div>
        </div>
    </div>

    <!-- Comment toggle menu box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{ xe_trans('xe::toggleMenu') }}</h3>
            </div>
            <div class="pull-right">
                <a data-toggle="collapse" data-parent="#accordion" href="#commentToggleMenu" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">메뉴닫기</span></a>
            </div>
        </div>
        <div id="commentToggleMenu" class="panel-collapse collapse in">
            <div class="panel-body">
                {!! $toggleMenuSection !!}
            </div>
        </div>
    </div>


</div>

<script type="text/javascript">
    $(function () {
        $('.__xe_inherit', '#fCommentSetting').click(function (e) {
            var $group = $(this).closest('.form-group');
            $('input,select,textarea', $group).not(this).prop('disabled', $(this).is(':checked'));
        }).each(function () {
            $(this).triggerHandler('click');
        });

    });
</script>
