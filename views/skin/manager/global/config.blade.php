<div class="panel-group" role="tablist" aria-multiselectable="true">
    <!-- Comment dynamic field box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{xe_trans('comment::manage.globalSetting')}}</h3>
            </div>
        </div>
        <div class="panel-collapse collapse in">
            <form method="post" action="{{ route('comment::setting.global.config') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="panel-body">
                    <div class="panel">
                        <div class="panel-heading">
                            <div class="pull-left">
                                <h4 class="panel-title">{{ xe_trans('comment::manage.setting.basic') }}</h4>
                            </div>
                        </div>
                        <div class="panel-body">

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
                                            <option value="{{ Xpressengine\Plugins\Comment\Handler::REMOVE_BLIND }}" @if($config->get('removeType') == Xpressengine\Plugins\Comment\Handler::REMOVE_BLIND) selected @endif>{{ xe_trans('comment::removeBlind') }}</option>
                                            <option value="{{ Xpressengine\Plugins\Comment\Handler::REMOVE_UNABLE }}" @if($config->get('removeType') == Xpressengine\Plugins\Comment\Handler::REMOVE_UNABLE) selected @endif>{{ xe_trans('comment::removeUnable') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
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