@section('page_title')
    <h2>{{xe_trans('comment::manage.globalSetting')}}</h2>
@endsection

@section('page_description')
    <small>{{ xe_trans('comment::manage.globalSettingDesc') }}</small>
@endsection

<div class="panel-group" role="tablist" aria-multiselectable="true">
    <!-- Comment dynamic field box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{xe_trans('comment::manage.globalSetting')}}</h3>
            </div>
        </div>
        <div class="panel-collapse collapse in">
            <form method="post" action="{{ route('manage.comment.setting.global') }}">
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

                            {{--<div class="col-sm-6">--}}
                            {{--<div class="form-group">--}}
                            {{--<div class="clearfix">--}}
                            {{--<label>{{ xe_trans('comment::manage.removeType') }}</label>--}}
                            {{--</div>--}}
                            {{--<select name="removeType" class="form-control">--}}
                            {{--<option value="batch" @if($config->get('removeType') == 'batch') selected @endif>일괄 삭제</option>--}}
                            {{--<option value="sr-only" @if($config->get('removeType') == 'sr-only') selected @endif>해당 글 가리기</option>--}}
                            {{--</select>--}}
                            {{--</div>--}}
                            {{--</div>--}}
                            {{--</div>--}}

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>{{ xe_trans('comment::manage.perPage') }}</label>
                                        </div>
                                        <input type="text" class="form-control" name="perPage" value="{{ $config->get('perPage') }}">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <div class="clearfix">
                                            <label>{{ xe_trans('comment::manage.ordering') }}</label>
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
                                <h4>{{ xe_trans('comment::manage.permission.create') }}</h4>
                                <div class="well">
                                    {!! uio('permission', $permArgs['create']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="panel-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary btn-lg">{{ xe_trans('xe::save') }}</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

</div>
