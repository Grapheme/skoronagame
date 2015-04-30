@extends(Helper::acclayout())
@section('style')
@stop
@section('content')
    @include($module['tpl'].'.question_menu')
    {{ Form::model($question,array('route'=>array('question.update',$question->id),'class'=>'smart-form','id'=>'question-form','role'=>'form','method'=>'put')) }}
    {{ Form::hidden('type') }}
    <div class="row">
        <section class="col col-6">
            <div class="well">
                <header>Редактирование вопроса:</header>
                <fieldset>
                    <section>
                        <label class="label">Название</label>
                        <label class="input">
                            {{ Form::text('title') }}
                        </label>
                    </section>
                    <section>
                        <label class="label">Текст вопроса</label>
                        <label class="input">
                            {{ Form::textarea('question',NULL,array('class'=>'redactor')) }}
                        </label>
                    </section>
                    <?php $answers = json_decode($question->answers);?>
                    @if(Request::segment(4) == 'quiz')
                        <section>
                            <label class="label">Ответ</label>
                            <label class="input">
                                {{ Form::hidden('current[0]',@$answers[0]->current) }}
                                {{ Form::text('answers[0]',@$answers[0]->title) }}
                            </label>
                        </section>
                    @else
                        <section>
                            <label class="label">Ответы</label>
                            @for($i=0;$i<5;$i++)
                                <label class="input">
                                    {{ Form::text("answers[$i]",isset($answers[$i]->title)?$answers[$i]->title:NULL) }}

                                </label>
                                <section>
                                    <label class="checkbox">
                                        {{ Form::checkbox("current[$i]",1,isset($answers[$i]->current) && $answers[$i]->current == 1 ?TRUE:FALSE,array('class'=>'js-set-current-answer')) }}
                                        <i></i>Правильный ответ
                                    </label>
                                </section>
                            @endfor
                        </section>
                    @endif
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
        var essence = 'question';
        var essence_name = 'вопрос';
        var validation_rules = {
            title: {required: true, maxlength: 100},
            question: {required: true, minlength: 1}
        };
        var validation_messages = {
            title: {required: "Укажите название"},
            question: {required: "Укажите текст вопроса"}
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
        $(".js-set-current-answer").change(function(){
            $(".js-set-current-answer").prop('checked',false);
            $(this).prop('checked',true);
        });
    </script>
@stop