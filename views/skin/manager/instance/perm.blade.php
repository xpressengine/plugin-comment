<div class="panel-group" role="tablist" aria-multiselectable="true">
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{xe_trans('xe::permission')}}</h3>
                <small><a href="{{ route('comment::setting.global.perm') }}" target="_blank">{{xe_trans('xe::moveToParentSettingPage')}}</a></small>
            </div>
        </div>
        <div id="commentBasic" class="panel-collapse collapse in">
            <form id="fCommentSetting" method="post" action="{{ route('comment::setting.perm', $targetInstanceId) }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="panel-body">

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
                                <label>{{ xe_trans('comment::manage.permission.manage') }}</label>
                                <div class="well">
                                    {!! uio('permission', $permArgs['manage']) !!}
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
</div>
