@extends(Helper::acclayout())
@section('style')
@stop
@section('content')
    @include($module['tpl'].'.badges_menu')
    {{ Form::model($badge,array('route'=>array('badges.update',$badge->id),'class'=>'smart-form','id'=>'badge-form','role'=>'form','method'=>'put')) }}
    {{ Form::hidden('type') }}
    <div class="row">
        <section class="col col-6">
            <div class="well">
                <header>Редактирование бейджа:</header>
                <fieldset>
                    <section>
                        <label class="label">Название</label>
                        <label class="input">
                            {{ Form::text('name') }}
                        </label>
                    </section>
                </fieldset>
                <footer>
                    <a class="btn btn-default no-margin regular-10 uppercase pull-left btn-spinner" href="{{URL::previous()}}">
                        <i class="fa fa-arrow-left hidden"></i> <span class="btn-response-text">Назад</span>
                    </a>
                    <button autocomplete="off" class="btn btn-success no-margin regular-10 uppercase btn-form-submit">
                        <i class="fa fa-spinner fa-spin hidden"></i> <span class="btn-response-text">Сохранить</span>
                    </button>
                </footer>
            </div>
        </section>
    </div>
    {{ Form::close() }}
@stop
@section('scripts')
    <script>
        var essence = 'badge';
        var essence_name = 'бейдж';
        var validation_rules = {
            name: {required: true, maxlength: 100}
        };
        var validation_messages = {
            name: {required: "Укажите название"}
        };
    </script>

    {{ HTML::script('private/js/modules/standard.js') }}

    {{ HTML::script('private/js/vendor/redactor.min.js') }}
    {{ HTML::script('private/js/system/redactor-config.js') }}
    <script type="text/javascript">
        if(typeof pageSetUp === 'function'){pageSetUp();}
        if(typeof runFormValidation === 'function') {
            loadScript("{{ asset('private/js/vendor/jquery-form.min.js'); }}", runFormValidation);
        } else {
            loadScript("{{ asset('private/js/vendor/jquery-form.min.js'); }}");
        }
    </script>
@stop