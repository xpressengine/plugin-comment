{{ XeFrontend::css('https://gitcdn.github.io/bootstrap-toggle/2.2.0/css/bootstrap-toggle.min.css')->before('https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css')->load() }}
{{ XeFrontend::js('https://gitcdn.github.io/bootstrap-toggle/2.2.0/js/bootstrap-toggle.min.js')->appendTo('head')->before('https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js')->load() }}

<div class="panel-group" id="accordion-comment" role="tablist" aria-multiselectable="true">
    <!-- Comment dynamic field box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{ xe_trans('comment::manage.setting.basic') }}</h3>
            </div>
            <div class="pull-right">
                <a data-toggle="collapse" data-parent="#accordion" href="#commentBasic" class="btn-link panel-toggle pull-right"><i class="xi-angle-down"></i><i class="xi-angle-up"></i><span class="sr-only">메뉴닫기</span></a>
            </div>
        </div>
        <div id="commentBasic" class="panel-collapse collapse in">
            <form id="fCommentSetting" method="post" action="{{ route('manage.comment.setting') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="instanceId" value="{{ $instanceId }}">

                <div class="panel-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.division') }}</label>
                                <input type="text" class="form-control" value="{{ $config->get('division') ? 'Used' : 'Unused' }}" readonly="readonly" disabled="disabled">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.useApprove') }}</label>
                                <select name="useApprove" class="form-control">
                                    <option value="true" @if($config->get('useApprove')) selected @endif>On</option>
                                    <option value="false" @if(!$config->get('useApprove')) selected @endif>Off</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.secret') }}</label>
                                <select name="secret" class="form-control">
                                    <option value="true" @if($config->get('secret')) selected @endif>On</option>
                                    <option value="false" @if(!$config->get('secret')) selected @endif>Off</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.assent') }}</label>
                                <select name="useAssent" class="form-control">
                                    <option value="true" @if($config->get('useAssent')) selected @endif>On</option>
                                    <option value="false" @if(!$config->get('useAssent')) selected @endif>Off</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.dissent') }}</label>
                                <select name="useDissent" class="form-control">
                                    <option value="true" @if($config->get('useDissent')) selected @endif>On</option>
                                    <option value="false" @if(!$config->get('useDissent')) selected @endif>Off</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.perPage') }}</label>
                                <input type="text" class="form-control" name="perPage" value="{{ $config->get('perPage') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.wysiwyg') }}</label>
                                <select name="useWysiwyg" class="form-control">
                                    <option value="true" @if($config->get('useWysiwyg')) selected @endif>On</option>
                                    <option value="false" @if(!$config->get('useWysiwyg')) selected @endif>Off</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{--<div class="col-sm-6">--}}
                            {{--<div class="form-group">--}}
                                {{--<label>{{ xe_trans('comment::manage.removeType') }}</label>--}}
                                {{--<select name="removeType" class="form-control">--}}
                                    {{--<option value="batch" @if($config->get('removeType') == 'batch') selected @endif>일괄 삭제</option>--}}
                                    {{--<option value="sr-only" @if($config->get('removeType') == 'sr-only') selected @endif>해당 글 가리기</option>--}}
                                {{--</select>--}}
                            {{--</div>--}}
                        {{--</div>--}}
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.ordering') }}</label>
                                <select name="reverse" class="form-control">
                                    <option value="false" @if(!$config->get('reverse')) selected @endif>{{ xe_trans('comment::forwardOrder') }}</option>
                                    <option value="true" @if($config->get('reverse')) selected @endif>{{ xe_trans('comment::inverseOrder') }}</option>
                                </select>
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

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>{{ xe_trans('comment::manage.permission.download') }}</label>
                                <div class="well">
                                    {!! uio('permission', $permArgs['download']) !!}
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
                <h3 class="panel-title">{{ xe_trans('comment::manage.setting.skin') }}</h3>
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

    <!-- Comment dynamic field box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{ xe_trans('comment::manage.setting.dynamicField') }}</h3>
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
                <h3 class="panel-title">{{ xe_trans('comment::manage.setting.toggleMenu') }}</h3>
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
//        $('input[name=useWysiwyg]', '#fCommentSetting').change(function () {
//            $('#commentEditor').toggleClass('hidden');
//        });

        $('#fCommentSetting').submit(function () {
            $('<input>').attr('type', 'hidden').attr('name', 'redirect').val(location.href).appendTo(this);
        });

    });
</script>
