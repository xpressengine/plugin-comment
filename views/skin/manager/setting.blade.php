
<div class="panel-group" id="accordion-comment" role="tablist" aria-multiselectable="true">
    <!-- Comment dynamic field box -->
    <div class="panel">
        <div class="panel-heading">
            <div class="pull-left">
                <h3 class="panel-title">{{ xe_trans('xe::comment') }} {{ xe_trans('comment::manage.setting.basic') }}</h3>
            </div>
        </div>
        <div id="commentBasic" class="panel-collapse collapse in">
            <form method="post" action="{{ route('manage.comment.setting.global') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <div class="panel-body">
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

</div>

<script type="text/javascript">
    $(function () {
//        $('input[name=useWysiwyg]').change(function () {
//            $('#commentEditor').toggleClass('hidden');
//        });

    });
</script>
